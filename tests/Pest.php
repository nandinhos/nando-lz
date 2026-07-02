<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Testes de Feature usam o TestCase da app e migram um banco limpo a cada run.
uses(TestCase::class, RefreshDatabase::class)->in('Feature');

// Testes de Unit não tocam o banco.
uses(TestCase::class)->in('Unit');
