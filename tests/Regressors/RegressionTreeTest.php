<?php

namespace Rubix\ML\Tests\Regressors;

use Rubix\ML\Learner;
use Rubix\ML\DataType;
use Rubix\ML\Estimator;
use Rubix\ML\Persistable;
use Rubix\ML\Graph\Trees\CART;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Graph\Trees\DecisionTree;
use Rubix\ML\Regressors\RegressionTree;
use Rubix\ML\Datasets\Generators\HalfMoon;
use Rubix\ML\CrossValidation\Metrics\RSquared;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use RuntimeException;

class RegressionTreeTest extends TestCase
{
    protected const TRAIN_SIZE = 300;
    protected const TEST_SIZE = 10;
    protected const MIN_SCORE = 0.9;

    protected const RANDOM_SEED = 0;

    /**
     * @var \Rubix\ML\Datasets\Generators\HalfMoon
     */
    protected $generator;

    /**
     * @var \Rubix\ML\Regressors\RegressionTree
     */
    protected $estimator;

    /**
     * @var \Rubix\ML\CrossValidation\Metrics\RSquared
     */
    protected $metric;

    public function setUp() : void
    {
        $this->generator = new HalfMoon(4., -7., 1., 90, 0.02);

        $this->estimator = new RegressionTree(10, 2, 3, 1e-7);

        $this->metric = new RSquared();

        srand(self::RANDOM_SEED);
    }

    public function test_build_regressor() : void
    {
        $this->assertInstanceOf(RegressionTree::class, $this->estimator);
        $this->assertInstanceOf(CART::class, $this->estimator);
        $this->assertInstanceOf(DecisionTree::class, $this->estimator);
        $this->assertInstanceOf(Learner::class, $this->estimator);
        $this->assertInstanceOf(Persistable::class, $this->estimator);
        $this->assertInstanceOf(Estimator::class, $this->estimator);

        $this->assertSame(Estimator::REGRESSOR, $this->estimator->type());

        $this->assertContains(DataType::CATEGORICAL, $this->estimator->compatibility());
        $this->assertContains(DataType::CONTINUOUS, $this->estimator->compatibility());

        $this->assertFalse($this->estimator->trained());

        $this->assertEquals(0, $this->estimator->height());
    }

    public function test_train_predict_feature_importances_rules() : void
    {
        $training = $this->generator->generate(self::TRAIN_SIZE);

        $testing = $this->generator->generate(self::TEST_SIZE);

        $this->estimator->train($training);

        $this->assertTrue($this->estimator->trained());

        $this->assertGreaterThan(0, $this->estimator->height());

        $predictions = $this->estimator->predict($testing);

        $score = $this->metric->score($predictions, $testing->labels());

        $this->assertGreaterThanOrEqual(self::MIN_SCORE, $score);

        $importances = $this->estimator->featureImportances();

        $this->assertCount(2, $importances);
        $this->assertEquals(1., array_sum($importances));

        $rules = $this->estimator->rules();

        $this->assertIsString($rules);
    }

    public function test_train_with_unlabeled() : void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->estimator->train(Unlabeled::quick());
    }

    public function test_predict_untrained() : void
    {
        $this->expectException(RuntimeException::class);

        $this->estimator->predict(Unlabeled::quick());
    }
}
