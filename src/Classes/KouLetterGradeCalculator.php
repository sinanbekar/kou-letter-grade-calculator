<?php

declare(strict_types=1);

namespace SinanBekar\Kou;

/**
 * KOU letter grade calculator. Supports curve.
 * @author Sinan Bekar <sinanbekar.work@gmail.com> 
 */
class LetterGradeCalculator
{

    protected float $studentDsn = 0;
    protected int $finalGrade = 0;
    protected float $classDsn = 0;
    protected float $standardDeriation = 0;
    protected float $tStandardGrade = 0;
    protected string $letterGrade;

    protected array $tStandardData;

    protected \PHPHtmlParser\Dom $courseDom;

    public function __construct(\PHPHtmlParser\Dom $courseDom, float $studentDsn, int $finalGrade)
    {
        $this->studentDsn = $studentDsn;
        $this->finalGrade = $finalGrade;
        $this->classDsn = IstatistikWrapper::getGradeAverageOfClass($courseDom);
        $this->standardDeriation = IstatistikWrapper::getStandardDeriationValue($courseDom);
        $this->tStandardData = IstatistikWrapper::getTStandardInfo($courseDom);
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
     *  Delete digits without rounding
     *  @param float $number
     *  @param int $precision
     *  @return float
     */
    public function truncateNumber(float $number, int $precision = 2): float
    {
        if (!function_exists('bcdiv')) {
            // Zero causes issues, and no need to truncate
            if (0 == (int)$number) {
                return $number;
            }
            // Are we negative?
            $negative = $number / abs($number);
            // Cast the number to a positive to solve rounding
            $number = abs($number);
            // Calculate precision number for dividing / multiplying
            $precision = pow(10, $precision);
            // Run the math, re-applying the negative value to ensure returns correctly negative / positive
            return (float)(floor($number * $precision) / $precision * $negative);
        } else {
            return (float)(bcdiv((string)($number), '1', $precision));
        }
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
                if ($this->standardDeriation != 0) {

                    $this->tStandardGrade = $this->truncateNumber(
                        (
                            (($this->studentDsn - $this->classDsn) / $this->standardDeriation) * 10 + 50)
                    );
                } else {
                    $this->tStandardGrade = $this->studentDsn;
                }

                if ($this->tStandardGrade > 100) {
                    $this->tStandardGrade = 100;
                }

                foreach ($this->tStandardData['tStandardRangeData'] as $data) {
                    sscanf($data['range'], "%f - %f", $min, $max);
                    if ($this->tStandardGrade >= $min && $this->tStandardGrade <= $max) {
                        $this->letterGrade = $data['letterGrade'];
                        break;
                    }
                }
            }
        }
        return $this->letterGrade;
    }
}
