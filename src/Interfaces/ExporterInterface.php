<?php

namespace MysqlBackup\Interfaces;

interface ExporterInterface
{
    public function export(string $filename): string;
}