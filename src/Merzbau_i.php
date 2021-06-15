<?php
/* This file is part of Merz | SSITU | (c) 2021 I-is-as-I-does | MIT License */

namespace SSITU\Merz;

interface Merzbau_i
{
    public function __construct($configPath);
    public function runJob(string $jobId, string $profile = '');
}
