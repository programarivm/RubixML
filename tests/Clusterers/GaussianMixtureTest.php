<?php

namespace Rubix\ML\Tests\Clusterers;

use Rubix\ML\Learner;
use Rubix\ML\Verbose;
use Rubix\ML\DataType;
use Rubix\ML\Estimator;
use Rubix\ML\Persistable;
use Rubix\ML\Probabilistic;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Other\Loggers\BlackHole;
use Rubix\ML\Clusterers\Seeders\KMC2;
use Rubix\ML\Datasets\Generators\Blob;
use Rubix\ML\Clusterers\GaussianMixture;
use Rubix\ML\Datasets\Generators\Agglomerate;
use Rubix\ML\CrossValidation\Metrics\VMeasure;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use RuntimeException;

class GaussianMixtureTest extends TestCase
{
    protected const TRAIN_SIZE = 350;
    protected const TEST_SIZE = 10;
    protected const MIN_SCORE = 0.9;

    protected const RANDOM_SEED = 0;

    /**
     * @var \Rubix\ML\Datasets\Generators\Agglomerate
     */
    protected $generator;

    /**
     * @var \Rubix\ML\Clusterers\GaussianMixture
     */
    protected $estimator;

    /**
     * @var \Rubix\ML\CrossValidation\Metrics\VMeasure
     */
    protected $metric;

    public function setUp() : void
    {
        $this->generator = new Agglomerate([
            'red' => new Blob([255, 32, 0], 30.),
            'green' => new Blob([0, 128, 0], 10.),
            'blue' => new Blob([0, 32, 255], 20.),
        ], [2, 3, 4]);

        $this->estimator = new GaussianMixture(3, 100, 1e-3, new KMC2(50));

        $this->metric = new VMeasure();

        $this->estimator->setLogger(new BlackHole());

        srand(self::RANDOM_SEED);
    }

    public function test_build_clusterer() : void
    {
        $this->assertInstanceOf(GaussianMixture::class, $this->estimator);
        $this->assertInstanceOf(Learner::class, $this->estimator);
        $this->assertInstanceOf(Probabilistic::class, $this->estimator);
        $this->assertInstanceOf(Verbose::class, $this->estimator);
        $this->assertInstanceOf(Persistable::class, $this->estimator);
        $this->assertInstanceOf(Estimator::class, $this->estimator);

        $this->assertSame(Estimator::CLUSTERER, $this->estimator->type());

        $this->assertNotContains(DataType::CATEGORICAL, $this->estimator->compatibility());
        $this->assertContains(DataType::CONTINUOUS, $this->estimator->compatibility());

        $this->assertFalse($this->estimator->trained());
    }

    public function test_train_predict() : void
    {
        $training = $this->generator->generate(self::TRAIN_SIZE);

        $testing = $this->generator->generate(self::TEST_SIZE);

        $this->estimator->train($training);

        $this->assertTrue($this->estimator->trained());

        $predictions = $this->estimator->predict($testing);

        $score = $this->metric->score($predictions, $testing->labels());

        $this->assertGreaterThanOrEqual(self::MIN_SCORE, $score);
    }

    public function test_train_incompatible() : void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->estimator->train(Unlabeled::quick([['bad']]));
    }

    public function test_predict_untrained() : void
    {
        $this->expectException(RuntimeException::class);

        $this->estimator->predict(Unlabeled::quick());
    }
}
