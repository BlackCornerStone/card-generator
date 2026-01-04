<?php

namespace CardGenerator\Repository;

use CardGenerator\DTO\Model\AbstractModelDTO;
use CardGenerator\Utils\RepositoryUtils;

abstract class AbstractRepository
{
    protected string $dataDir;
    /** @var array<string, ModelDTO>|null */
    private ?array $cache = null;

    public function __construct(string $dataDir = __DIR__ . '/../../data')
    {
        $this->dataDir = $dataDir;
    }

    /** @return string CSV file name (with extension) inside dataDir */
    abstract public function getCsvFile(): string;

    /**
     * Create a Source DTO instance.
     *
     * @param array<string, mixed> $data
     * @return mixed SourceDTO
     */
    abstract protected function createSource(array $data);

    /**
     * Create a Model DTO (decorator) from the Source DTO.
     *
     * @param mixed $source SourceDTO
     * @return mixed ModelDTO
     */
    abstract protected function createModel($source);

    /**
     * @param mixed $model ModelDTO
     * @param array<string, mixed> $data
     */
    protected function applyComputed($model, array $data): void
    {
        // default: nothing
    }

    /**
     * @param mixed $model ModelDTO
     * @param array<string, array{dataset:string, key:string}> $links
     */
    protected function applyLinks($model, array $links): void
    {
        // default: nothing
    }

    /**
     * Return an iterator yielding name => ModelDTO without caching, so callers can discover
     * the exact row that fails (e.g., during validation) without hiding exceptions.
     *
     * @return \Generator<string, AbstractModelDTO> yields ModelDTO
     */
    public function findAll(): \Generator
    {
        $path = rtrim($this->dataDir, '/').'/'.$this->getCsvFile();
        $rows = RepositoryUtils::readCsv($path);

        foreach ($rows as $index => $row) {
            [$data, $links] = RepositoryUtils::normalizeRow($row);
            $source = $this->createSource($data);
            $model = $this->createModel($source);
            $this->applyComputed($model, $data);
            $this->applyLinks($model, $links);
            $name = (string)($data['Name'] ?? '');
            yield $name !== '' ? $name : $index => $model;
        }
    }

    public function findByName(string $name)
    {
        foreach ($this->findAll() as $key => $model) {
            if ($key === $name) {
                return $model;
            }
        }
        return null;
    }
}
