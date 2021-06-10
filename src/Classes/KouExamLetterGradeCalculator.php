<?php

declare(strict_types=1);

namespace SinanBekar\Kou;

/**
 * KOU exam letter grade calculator. Supports curve.
 * @author Sinan Bekar <sinanbekar.work@gmail.com> 
 */
class ExamLetterGradeCalculator
{

    protected float $studentDsn = 0;
    protected int $finalGrade = 0;
    protected float $classDsn = 0;
    protected float $standartDeriation = 0;
    protected float $tStandartGrade = 0;
    protected string $letterGrade;

    protected array $tStandartData;

    protected \PHPHtmlParser\Dom $courseDom;

    public function __construct(\PHPHtmlParser\Dom $courseDom, float $studentDsn, int $finalGrade)
    {
        $this->studentDsn = $studentDsn;
        $this->finalGrade = $finalGrade;
        $this->classDsn = IstatistikWrapper::getGradeAverageOfClass($courseDom);
        $this->standartDeriation = IstatistikWrapper::getStandartDeriationValue($courseDom);
        $this->tStandartData = IstatistikWrapper::getTStandartInfo($courseDom);
    }

    /**
     * Returns student grades average.
     * @param float $midTermAverage Mid-Term grades average
     * @param int $finalGrade Final grade
     * @param int $midTermPercent Mid-Term grades percent
     * @param int $finalPercent Final grade percent
     * @return float
     */
    public static function calculateDsn(float $midTermAverage, int $finalGrade, int $midTermPercent, int $finalPercent): float
    {
        return (($midTermAverage * $midTermPercent) / 100) + (($finalGrade * $finalPercent) / 100);
    }

    /**
     * Returns letter grade.
     * @return string
     */
    public function getLetterGrade(): string
    {
        if (empty($this->letterGrade)) {
            if ($this->studentDsn < 15 || $this->finalGrade < 40) {
                $this->letterGrade = "FF";
            } else {
                if ($this->standartDeriation != 0) {
                    $this->tStandartGrade = (float)bcdiv((string)((($this->studentDsn - $this->classDsn) / $this->standartDeriation) * 10 + 50), '1', 2);
                    // Delete digits without rounding
                } else {
                    $this->tStandartGrade = $this->studentDsn;
                }

                if ($this->tStandartGrade > 100) {
                    $this->tStandartGrade = 100;
                }

                foreach ($this->tStandartData['tStandartRangeData'] as $data) {
                    sscanf($data['range'], "%f - %f", $min, $max);
                    if ($this->tStandartGrade >= $min && $this->tStandartGrade <= $max) {
                        $this->letterGrade = $data['letterGrade'];
                        break;
                    }
                }
            }
        }
        return $this->letterGrade;
    }
}
