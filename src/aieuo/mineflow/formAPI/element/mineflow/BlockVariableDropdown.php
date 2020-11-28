<?php

namespace aieuo\mineflow\formAPI\element\mineflow;

use aieuo\mineflow\flowItem\FlowItemIds;
use aieuo\mineflow\variable\DummyVariable;

class BlockVariableDropdown extends VariableDropdown {

    protected $variableType = DummyVariable::BLOCK;

    protected $actions = [
        FlowItemIds::CREATE_BLOCK_VARIABLE,
        FlowItemIds::GET_BLOCK,
        FlowItemIds::GET_TARGET_BLOCK,
    ];

    public function __construct(array $variables = [], string $default = "", ?string $text = null, bool $optional = false) {
        parent::__construct($text ?? "@action.form.target.block", $variables, [DummyVariable::BLOCK], $default, $optional);
    }
}