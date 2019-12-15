<?php

namespace Rubix\ML\Datasets\Extractors;

use Rubix\ML\Datasets\Extractors\Traits\Cursorable;
use InvalidArgumentException;
use RuntimeException;

/**
 * NDJSON Array
 *
 * NDJSON or *Newline Delimited* JSON files contain rows of data encoded in Javascript Object
 * Notation (JSON) arrays. The format is similar to CSV but has the advantage of being
 * standardized and retaining data type information at the cost of having a slightly heavier
 * footprint.
 *
 * > **Note:** Empty rows will be ignored by the parser by default.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class NDJSONArray implements Extractor
{
    use Cursorable;

    /**
     * The path to the NDJSON file.
     *
     * @var string
     */
    protected $path;

    /**
     * @param string $path
     * @throws \InvalidArgumentException
     */
    public function __construct(string $path)
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException("File at $path does not exist.");
        }
        
        if (!is_readable($path)) {
            throw new InvalidArgumentException("File at $path is not readable.");
        }

        $this->path = $path;
    }

    /**
     * Read the records starting at the given offset and return them in an iterator.
     *
     * @throws \RuntimeException
     * @return iterable
     */
    public function extract() : iterable
    {
        $handle = fopen($this->path, 'r');
        
        if (!$handle) {
            throw new RuntimeException("Could not open file at {$this->path}.");
        }

        $line = $n = 0;

        while (!feof($handle)) {
            $row = fgets($handle);

            if (empty($row)) {
                continue 1;
            }

            ++$line;

            if ($line > $this->offset) {
                $record = json_decode($row);

                if (!is_array($record)) {
                    throw new RuntimeException('Non JSON array found'
                        . " at row $line.");
                }

                yield $record;

                ++$n;

                if ($n >= $this->limit) {
                    break 1;
                }
            }
        }

        fclose($handle);
    }
}
