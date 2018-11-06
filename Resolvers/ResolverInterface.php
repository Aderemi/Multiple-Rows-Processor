<?php

namespace MultipleRows\Resolvers;


use MultipleRows\Exceptions\MultipleRowsException;

interface ResolverInterface
{

	public function validate(): bool;

	/**
     * @param $rule
     * @param $value
     * @return bool
     * @throws MultipleRowsException
     */
	public function ruleTest(string $rule, string $value) : bool;

    public function getError(): string;
}