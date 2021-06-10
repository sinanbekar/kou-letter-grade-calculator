<?php

declare(strict_types=1);

require __DIR__ . "/Classes/KouIstatistikWrapper.php";
require __DIR__ . "/Classes/KouExamLetterGradeCalculator.php";

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\Twig;

use SinanBekar\Kou\IstatistikWrapper;
use SinanBekar\Kou\ExamLetterGradeCalculator;

$app->group('/api', function (RouteCollectorProxy $group) {

    $group->get('/academic-terms', function (Request $request, Response $response, $args) {
        $data = (new IstatistikWrapper)->getAcademicTerms();
        $data = !empty($data) ? $data : [];
        $response->getBody()->write(json_encode(['success' => !empty($data) ? true : false, 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $group->get('/faculties', function (Request $request, Response $response, $args) {
        $data = (new IstatistikWrapper)->getFaculties();
        $data = !empty($data) ? $data : [];
        $response->getBody()->write(json_encode(['success' => !empty($data) ? true : false, 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(!empty($data) ? 200 : 400);
    });

    $group->get('/schools', function (Request $request, Response $response, $args) {
        $data = (new IstatistikWrapper)->getSchools();
        $data = !empty($data) ? $data : [];
        $response->getBody()->write(json_encode(['success' => !empty($data) ? true : false, 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(!empty($data) ? 200 : 400);
    });

    $group->get('/vocational-schools', function (Request $request, Response $response, $args) {
        $data = (new IstatistikWrapper)->getVocationalSchools();
        $data = !empty($data) ? $data : [];
        $response->getBody()->write(json_encode(['success' => !empty($data) ? true : false, 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(!empty($data) ? 200 : 400);
    });

    $group->get('/departments', function (Request $request, Response $response, $args) {
        $cachePath = realpath(sys_get_temp_dir()) . '/' . md5('departmentsCache') . '.json';
        if (file_exists($cachePath)) {
            if (time() < filemtime($cachePath) + (24 * 60 * 60)) {
                $data = json_decode(file_get_contents($cachePath), true);
            } else {
                unlink($cachePath);
                $data = (new IstatistikWrapper)->getAllDepartments();
            }
        } else {
            $data = (new IstatistikWrapper)->getAllDepartments();
            file_put_contents($cachePath, json_encode($data));
        }
        $data = !empty($data) ? $data : [];
        $response->getBody()->write(json_encode(['success' => !empty($data) ? true : false, 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(!empty($data) ? 200 : 400);
    });

    $group->get('/departments/{unitKey}/{facultyKey}', function (Request $request, Response $response, $args) {
        try {
            $data = (new IstatistikWrapper)->getDepartments($args['facultyKey'], $args['unitKey']);
        } catch (\Throwable $e) {
            $data = [];
        }
        $data = !empty($data) ? $data : [];
        $response->getBody()->write(json_encode(['success' => !empty($data) ? true : false, 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(!empty($data) ? 200 : 400);
    });

    $group->get('/courses/{departmentKey}[/{academicTermKey}]', function (Request $request, Response $response, $args) {
        try {
            $data = (new IstatistikWrapper)->getCourses($args['departmentKey'], $args['academicTermKey'] ?? "default");
        } catch (\Throwable $e) {
            $data = [];
        }
        $data = !empty($data) ? $data : [];
        $response->getBody()->write(json_encode(['success' => !empty($data) ? true : false, 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(!empty($data) ? 200 : 400);
    });

    $group->post('/course', function (Request $request, Response $response, $args) {
        $args['method'] = $args['method'] ?? 'all';
        $postData = (array)$request->getParsedBody();
        if (isset($postData['courseKey'])) {
            try {
                $wrapper = new IstatistikWrapper;
                $dom = $wrapper->getCourseDom($postData['courseKey'], $postData['academicTermKey'] ?? "default");
                if (!empty($dom)) {
                    $data['courseName'] = $wrapper::getCourseName($dom);
                    $data['gradeAverageOfClass'] = $wrapper::getGradeAverageOfClass($dom);
                    $data['standartDeriationValue'] = $wrapper::getStandartDeriationValue($dom);
                    if (
                        isset($postData['midTermAverage'])
                        && isset($postData['midTermPercent'])
                        && isset($postData['finalGrade'])
                        && isset($postData['finalPercent'])
                    ) {
                        $data['studentDsn'] = ExamLetterGradeCalculator::calculateDsn(
                            (float)$postData['midTermAverage'],
                            (int)$postData['finalGrade'],
                            (int)$postData['midTermPercent'],
                            (int)$postData['finalPercent']
                        );
                        if ($data['studentDsn'] > 100 || ((int)$postData['midTermPercent'] + (int)$postData['finalPercent']) !== 100 ) {
                            throw new \UnexpectedValueException;
                        }
                        $data['letterGrade'] =
                            (new ExamLetterGradeCalculator(
                                $dom,
                                $data['studentDsn'],
                                (int)$postData['finalGrade']
                            ))->getLetterGrade();
                    }
                    $data['tStandartInfo'] = $wrapper::getTStandartInfo($dom);
                }
            } catch (\Throwable $e) {
                $data = [];
            }
        }
        $data = !empty($data) ? $data : [];
        $response->getBody()->write(json_encode(['success' => !empty($data) ? true : false, 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(!empty($data) ? 200 : 400);
    });
});

$app->get('/', function (Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'view.twig');
});

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/api[{routes:.+}]', function ($request, $response) {
    $response->getBody()->write(json_encode(['success' => false, 'data' => []]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
});

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    $response->getBody()->write('404 Error');
    return $response->withStatus(404);
});
