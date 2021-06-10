<?php

declare(strict_types=1);

namespace SinanBekar\Kou;

use \GuzzleHttp\Client;
use \GuzzleHttp\Psr7\Request;
use \PHPHtmlParser\Dom;

/**
 * KOUIstatistik Wrapper
 * @author Sinan Bekar <sinanbekar.work@gmail.com> 
 */
class IstatistikWrapper
{

    const KOUBS_ISTATISTIK_NOTD_URL = "https://ogr.kocaeli.edu.tr/KOUBS/Istatistik/NotDuniversite_Bologna.cfm";

    /**
     * PSR-7 Client interface.
     */
    protected Client $client;

    /**
     * PSR-7 Request interface.
     */
    protected Request $request;

    protected string $academicTermKey = "default";
    protected string $unitKey = "default";
    protected string $facultyKey = "default";
    protected string $departmentKey = "default";
    protected string $courseKey = "default";

    protected string $currentDepartmentParentName = "";

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Returns DOM for parsing.
     * @param string $url
     * @param string $method
     * @param array $params
     * @return \PHPHtmlParser\Dom|null
     */
    protected function initDom(string $url, string $method = "POST", array $params = []): ?DOM
    {
        $this->request = new Request(
            $method,
            $url,
            $method === "POST" ? [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ] : [],
            $method === "POST" ? http_build_query($params) : null
        );
        $dom = new Dom;
        $dom->loadFromUrl($url, null, $this->client, $this->request);
        if (str_contains($dom->outerHtml, 'Error Occurred While Processing Request')) {
            $dom = null;
        }
        return $dom;
    }

    /**
     * Main Request Builder
     * @param string $selectName Select that will parsed.
     * @return array
     */
    protected function requestBuilder(string $selectName): array
    {
        if ($this->academicTermKey === "default") {
            $this->academicTermKey = $this->getCurrentAcademicTermKey();
        }

        $postParams = [
            'Donem' => $this->academicTermKey,
            'Universite' => $this->unitKey,
            'fakulte' => $this->facultyKey,
            'Bolum' => $this->departmentKey,
            'Ders' => $this->courseKey
        ];

        $dom = $this->initDom(self::KOUBS_ISTATISTIK_NOTD_URL, 'POST', $postParams);

        if (empty($dom)) {
            return [];
        }

        if ($selectName === "Bolum") {
            $this->currentDepartmentParentName = trim($dom->find("select[name=fakulte] option[selected]")->text);
        }

        $data = $dom->find("select[name=$selectName] option");

        if ($data[0]->getAttribute('value') === 'default') {
            unset($data[0]); // Delete "Tumu"
        }

        $listData = [];
        foreach ($data as $single) {
            $listData[] = ['key' => $single->getAttribute('value'), 'text' => trim($single->text)];
        }
        return $listData;
    }

    /**
     * Returns current academic term.
     * TODO dynamic without requesting.
     * @return string
     */
    public function getCurrentAcademicTermKey(): string
    {
        return "2021B";
    }

    /**
     * Returns academic terms.
     * @return array
     */
    public function getAcademicTerms(): array
    {
        return $this->requestBuilder('donem');
    }

    /**
     * Returns faculties as array with text and specific key.
     * @param string $academicTermKey
     * @return array
     */
    public function getFaculties(): array
    {
        $this->unitKey = "1";
        return $this->requestBuilder('fakulte');
    }

    /**
     * Returns schools as array with text and specific key.
     * @return array
     */
    public function getSchools(): array
    {
        $this->unitKey = "2";
        return $this->requestBuilder('fakulte');
    }

    /**
     * Returns vocational schools as array with text and specific key.
     * @return array
     */
    public function getVocationalSchools(): array
    {
        $this->unitKey = "3";
        return $this->requestBuilder('fakulte');
    }

    /**
     * Returns departments as array with text and specific key.
     * @param string $key
     * @param string $unitKey
     * @return array
     */
    public function getDepartments(string $key, string $unitKey): array
    {
        $this->unitKey = $unitKey;
        $this->facultyKey = $key;
        $departments = $this->requestBuilder('Bolum');
        return ['departmentsParentName' => $this->currentDepartmentParentName, 'departments' => $departments];
    }

    /**
     * Returns faculty departments as array with text and specific key.
     * @param string $facultyKey
     * @param string $academicTermKey
     * @return array
     */
    public function getFacultyDepartments(string $facultyKey): array
    {
        return $this->getDepartments($facultyKey, "1");
    }

    /**
     * Returns school departments as array with text and specific key.
     * @param string $schoolKey
     * @param string $academicTermKey
     * @return array
     */
    public function getSchoolDepartments(string $schoolKey): array
    {
        return $this->getDepartments($schoolKey, "2");
    }

    /**
     * Returns vocational school departments as array with text and specific key.
     * @param string $vocationalSchoolKey
     * @return array
     */
    public function getVocationalSchoolDepartments(string $vocationalSchoolKey): array
    {
        return $this->getDepartments($vocationalSchoolKey, "3");
    }

    /**
     * Returns courses as array with text and specific key.
     * @param string $departmentKey
     * @param string $academicTermKey
     * @return array
     */
    public function getCourses(string $departmentKey, string $academicTermKey = "default"): array
    {
        $this->academicTermKey = $academicTermKey;
        $this->unitKey = "0";
        $this->facultyKey = "0";
        $this->departmentKey = $departmentKey;
        $courses = $this->requestBuilder('Ders');
        return ['departmentName' => null, 'courses' => $courses]; // TODO
    }

    /**
     * Returns course DOM for parsing.
     * @param string $courseKey
     * @param string $academicTermKey
     * @return \PHPHtmlParser\Dom|null
     */
    public function getCourseDom(string $courseKey, string $academicTermKey = "default"): ?DOM
    {
        $this->academicTermKey = $academicTermKey;

        if ($this->academicTermKey === "default") {
            $this->academicTermKey = $this->getCurrentAcademicTermKey();
        }

        $this->unitKey = "0";
        $this->facultyKey = "0";
        $this->departmentKey = "0";
        $this->courseKey = $courseKey;

        $dom = $this->initDom(self::KOUBS_ISTATISTIK_NOTD_URL, 'POST', [
            'Donem' => $this->academicTermKey,
            'Universite' => $this->unitKey,
            'fakulte' => $this->facultyKey,
            'Bolum' => $this->departmentKey,
            'Ders' => $this->courseKey,
            'Ara' => 'Göster'
        ]);
        return $dom;
    }

    /**
     * Returns course name.
     * @param \PHPHtmlParser\Dom $courseDom 
     * @return string
     */
    public static function getCourseName(DOM $courseDom): string
    {
        return trim($courseDom->find('table')[1]->find('tr')[2]->find('font')->text);
    }

    /**
     * Returns grade average of class.
     * @param \PHPHtmlParser\Dom $courseDom 
     * @return float
     */
    public static function getGradeAverageOfClass(DOM $courseDom): float
    {
        try {
            return (float)trim($courseDom->find('table')[1]->find('tr')[7]->find('i b')->text);
        } catch (\Throwable $e) {
            return 0.00;
        }
    }

    /**
     * Returns standart deriation value.
     * @param \PHPHtmlParser\Dom $courseDom 
     * @return float
     */
    public static function getStandartDeriationValue(DOM $courseDom): float
    {
        try {
            return (float)trim($courseDom->find('table')[1]->find('tr')[8]->find('i b')->text);
        } catch (\Throwable $e) {
            return 0.00;
        }
    }

    /**
     * Returns T-Standart info as array.
     * @param \PHPHtmlParser\Dom $courseDom 
     * @return array
     */
    public static function getTStandartInfo(DOM $courseDom): array
    {
        $tStandartDom = $courseDom->find('.col-lg-5 .col-lg-12 .bg-primary.col-lg-2')->getParent();
        $tStandartInfo = [];
        $tStandartInfo['classDsnSuccessLabel'] = trim($tStandartDom->find('.col-lg-1')->text);
        $tStandartInfo['dsnRange'] = explode(' < />iv> ', $tStandartDom->find('.col-lg-2')[0]->innerHtml)[0]; // TODO : Fix ugly workaround

        if ($tStandartInfo['classDsnSuccessLabel'] !== "Üstün Başarı") {
            foreach ($tStandartDom->find('.col-lg-1.bg-primary') as $k => $gradeRange) {
                $letterSortingArray = ['FF', 'FD', 'DD', 'DC', 'CC', 'CB', 'BB', 'BA', 'AA'];
                $tStandartInfo['tStandartRangeData'][] = ['letterGrade' => $letterSortingArray[$k], 'range' => trim($gradeRange->text)];
            }
        } else {
            $tStandartInfo['tStandartRangeData'][] = ['letterGrade' => 'FF', 'range' => '0 - 29'];
            $tStandartInfo['tStandartRangeData'][] = ['letterGrade' => 'FD', 'range' => '30 - 39'];
            $tStandartInfo['tStandartRangeData'][] = ['letterGrade' => 'DD', 'range' => '40 - 49'];
            $tStandartInfo['tStandartRangeData'][] = ['letterGrade' => 'DC', 'range' => '50 - 59'];
            $tStandartInfo['tStandartRangeData'][] = ['letterGrade' => 'CC', 'range' => '60 - 69'];
            $tStandartInfo['tStandartRangeData'][] = ['letterGrade' => 'CB', 'range' => '70 - 74'];
            $tStandartInfo['tStandartRangeData'][] = ['letterGrade' => 'BB', 'range' => '75 - 79'];
            $tStandartInfo['tStandartRangeData'][] = ['letterGrade' => 'BA', 'range' => '80 - 89'];
            $tStandartInfo['tStandartRangeData'][] = ['letterGrade' => 'AA', 'range' => '90 - 100'];
        }
        return $tStandartInfo;
    }

    /**
     * Returns all departments with divided by each department parent.
     * @param $academicTermKey Academic term key. E.g. 2021B
     * @return array
     *! Not recommended as direct call due to long execution time. Use with cache. 
     */
    public function getAllDepartments(): array
    {
        $departments = [];
        foreach ($this->getFaculties() as $faculty) {
            if (!empty($faculty)) {
                $departmentsMainData = $this->getFacultyDepartments($faculty['key']);
                $departments[] = [
                    'unitKey' => '1',
                    'unitName' => 'Fakülte',
                    'departmentsParentName' => $this->currentDepartmentParentName,
                    'key' => $faculty['key'],
                    'departments' => $departmentsMainData['departments']
                ];
                usleep(100000);
            }
        }
        foreach ($this->getSchools() as $school) {
            if (!empty($school)) {
                $departmentsMainData = $this->getSchoolDepartments($school['key']);
                $departments[] = [
                    'unitKey' => '2',
                    'unitName' => 'Yüksekokul',
                    'departmentsParentName' =>  $this->currentDepartmentParentName,
                    'key' => $school['key'],
                    'departments' => $departmentsMainData['departments']
                ];
                usleep(100000);
            }
        }
        foreach ($this->getVocationalSchools() as $vocationalSchool) {
            if (!empty($vocationalSchool)) {
                $departmentsMainData = $this->getVocationalSchoolDepartments($vocationalSchool['key']);
                $departments[] = [
                    'unitKey' => '3',
                    'unitName' => 'Meslek Yüksekokulu',
                    'departmentsParentName' =>  $this->currentDepartmentParentName,
                    'key' => $vocationalSchool['key'],
                    'departments' => $departmentsMainData['departments']
                ];
                usleep(100000);
            }
        }
        return $departments;
    }

    /**
     * Not recommended direct call due to long run execution use. Use inside the worker and cache. 
     */
    /*
    public function getAllCourses(string $academicTermKey = "default"): array
    {
        $courses = [];
        foreach ($this->getAllDepartments($academicTermKey) as $facultyDepartmentData) {
            foreach ($facultyDepartmentData['departments'] as $department) {
                $courses[] = $this->getDepartmentCourses($department['key'], $facultyDepartmentData['key'], $facultyDepartmentData['unitKey'], $academicTermKey);
                usleep(100000);
            }
            usleep(100000);
        }
        return $courses;
    }
    */

    /**
     * TODO
     */
    public function getGradeCountOfClass($letterGrade = null)
    {
        // TODO
        return null;
    }

    /**
     * TODO
     */
    public function getGradeRateOfClass($letterGrade = null)
    {
        // TODO
        return null;
    }
}
