<?php

namespace Rubix\ML\Tests\CrossValidation;

use Rubix\ML\Parallel;
use Rubix\ML\Backends\Serial;
use Rubix\ML\Datasets\Generators\Blob;
use Rubix\ML\CrossValidation\Validator;
use Rubix\ML\CrossValidation\MonteCarlo;
use Rubix\ML\Classifiers\DummyClassifier;
use Rubix\ML\Datasets\Generators\Agglomerate;
use Rubix\ML\CrossValidation\Metrics\Accuracy;
use PHPUnit\Framework\TestCase;

class MonteCarloTest extends TestCase
{
    protected const TRAIN_SIZE = 50;

    /**
     * @var \Rubix\ML\Datasets\Generators\Agglomerate
     */
    protected $generator;

    /**
     * @var \Rubix\ML\Classifiers\DummyClassifier
     */
    protected $estimator;

    /**
     * @var \Rubix\ML\CrossValidation\MonteCarlo
     */
    protected $validator;

    public function setUp() : void
    {
        $this->generator = new Agglomerate([
            'male' => new Blob([69.2, 195.7, 40.], [1., 3., 0.3]),
            'female' => new Blob([63.7, 168.5, 38.1], [0.8, 2.5, 0.4]),
        ], [0.45, 0.55]);

        $this->estimator = new DummyClassifier();

        $this->validator = new MonteCarlo(3, 0.2);

        $this->validator->setBackend(new Serial());
    }

    public function test_build_validator() : void
    {
        $this->assertInstanceOf(MonteCarlo::class, $this->validator);
        $this->assertInstanceOf(Validator::class, $this->validator);
        $this->assertInstanceOf(Parallel::class, $this->validator);
    }

    public function test_test() : void
    {
        $dataset = $this->generator->generate(self::TRAIN_SIZE);

        $score = $this->validator->test($this->estimator, $dataset, new Accuracy());

        $this->assertEqualsWithDelta(.5, $score, .5);
    }
}
