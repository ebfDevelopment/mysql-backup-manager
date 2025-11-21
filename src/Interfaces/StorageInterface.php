<?php

namespace MysqlBackup\Interfaces;

interface StorageInterface
{
    public function upload(string $filePath, string $filename): bool;
}