<?php

namespace Rubix\ML\CrossValidation;

use Rubix\ML\Estimator;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Metrics\Validation\Validation;
use InvalidArgumentException;

class Holdout implements Validator
{
    /**
     * The metric used to score the predictions.
     *
     * @var \Rubix\ML\Metrics\Validation
     */
    protected $metric;

    /**
     * The holdout ratio. i.e. the ratio of samples to use for validation.
     *
     * @var float
     */
    protected $ratio;

    /**
     * @param  \Rubix\ML\Metrics\Validation\Validation  $metric
     * @param  float  $ratio
     * @throws \InvalidArgumentException
     * @return void
     */
    public function __construct(Validation $metric, float $ratio = 0.2)
    {
        if ($ratio < 0.01 or $ratio > 1.0) {
            throw new InvalidArgumentException('Holdout ratio must be'
                . ' between 0.01 and 1.0.');
        }

        $this->metric = $metric;
        $this->ratio = $ratio;
    }

    /**
     * Run k training rounds where k is the number of folds. For each round use
     * one fold for testing and the rest to train the model. Return the average
     * score for each training round.
     *
     * @param  \Rubix\ML\Estimator\Estimator  $estimator
     * @param  \Rubix\ML\Datasets\Labeled  $dataset
     * @return float
     */
    public function test(Estimator $estimator, Labeled $dataset) : float
    {
        $dataset->randomize();

        if ($estimator instanceof Classifier or $estimator instanceof Clusterer) {
            list($training, $testing) =
                $dataset->stratifiedSplit(1 - $this->ratio);
        } else {
            list($training, $testing) =
                $dataset->split(1 - $this->ratio);
        }

        $estimator->train($training);

        $score = $this->metric->score($estimator, $testing);

        return $score;
    }
}