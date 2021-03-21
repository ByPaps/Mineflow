<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\exception\InvalidFlowValueException;
use aieuo\mineflow\flowItem\FlowItem;
use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\formAPI\element\CancelToggle;
use aieuo\mineflow\formAPI\element\Dropdown;
use aieuo\mineflow\formAPI\element\Label;
use aieuo\mineflow\formAPI\element\mineflow\ExampleInput;
use aieuo\mineflow\formAPI\Form;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\variable\DummyVariable;
use aieuo\mineflow\variable\ListVariable;
use aieuo\mineflow\variable\StringVariable;

class EditString extends FlowItem {

    protected $id = self::EDIT_STRING;

    protected $name = "action.editString.name";
    protected $detail = "action.editString.detail";
    protected $detailDefaultReplace = ["value1", "operator", "value2", "result"];

    protected $category = Category::STRING;
    protected $returnValueType = self::RETURN_VARIABLE_VALUE;

    public const TYPE_JOIN = "join";
    public const TYPE_DELETE = "delete";
    public const TYPE_REPEAT = "repeat";
    public const TYPE_SPLIT = "split";

    /** @var string[] */
    private $operators = [
        self::TYPE_JOIN,
        self::TYPE_DELETE,
        self::TYPE_REPEAT,
        self::TYPE_SPLIT,
    ];

    /** @var string */
    private $value1;
    /** @var string */
    private $operator;
    /** @var string */
    private $value2;
    /** @var string */
    private $resultName;

    public function __construct(string $value1 = "", string $operator = self::TYPE_JOIN, string $value2 = "", string $resultName = "result") {
        $this->value1 = $value1;
        $this->operator = $operator;
        $this->value2 = $value2;
        $this->resultName = $resultName;
    }

    public function setValues(string $value1, string $value2): self {
        $this->value1 = $value1;
        $this->value2 = $value2;
        return $this;
    }

    public function getValue1(): string {
        return $this->value1;
    }

    public function getValue2(): string {
        return $this->value2;
    }

    public function setOperator(string $operator): self {
        $this->operator = $operator;
        return $this;
    }

    public function getOperator(): string {
        return $this->operator;
    }

    public function setResultName(string $name): self {
        $this->resultName = $name;
        return $this;
    }

    public function getResultName(): string {
        return $this->resultName;
    }

    public function isDataValid(): bool {
        return $this->getValue1() !== "" and $this->getValue2() !== "" and $this->getOperator() !== "";
    }

    public function getDetail(): string {
        if (!$this->isDataValid()) return $this->getName();
        return Language::get($this->detail, [$this->getValue1(), ["action.editString.".$this->getOperator()], $this->getValue2(), $this->getResultName()]);
    }

    public function execute(Recipe $source): \Generator {
        $this->throwIfCannotExecute();

        $value1 = $source->replaceVariables($this->getValue1());
        $value2 = $source->replaceVariables($this->getValue2());
        $resultName = $source->replaceVariables($this->getResultName());
        $operator = $this->getOperator();

        switch ($operator) {
            case self::TYPE_JOIN:
                $result = new StringVariable($value1.$value2, $resultName);
                break;
            case self::TYPE_DELETE:
                $result = new StringVariable(str_replace($value2, "", $value1), $resultName);
                break;
            case self::TYPE_REPEAT:
                $this->throwIfInvalidNumber($value2, 1);
                $result = new StringVariable(str_repeat($value1, (int)$value2), $resultName);
                break;
            case self::TYPE_SPLIT:
                $result = new ListVariable(array_map(function (string $str) {
                    return new StringVariable($str);
                }, explode($value2, $value1)), $resultName);
                break;
            default:
                throw new InvalidFlowValueException($this->getName(), Language::get("action.calculate.operator.unknown", [$operator]));
        }

        $source->addVariable($result);
        yield true;
        return $result;
    }

    public function getEditFormElements(array $variables): array {
        return [
            new ExampleInput("@action.fourArithmeticOperations.form.value1", "10", $this->getValue1(), true),
            new Dropdown("@action.fourArithmeticOperations.form.operator", array_map(function (string $type) {
                return Language::get("action.editString.".$type);
            }, array_values($this->operators)), array_shift($keys) ?? 0),
            new ExampleInput("@action.fourArithmeticOperations.form.value2", "50", $this->getValue2(), true),
            new ExampleInput("@action.form.resultVariableName", "result", $this->getResultName(), true),
        ];
    }

    public function parseFromFormData(array $data): array {
        return ["contents" => [$data[0], $this->operators[$data[1]], $data[2], $data[3]]];
    }

    public function loadSaveData(array $content): FlowItem {
        $this->setValues($content[0], $content[2]);
        $this->setOperator((string)$content[1]);
        $this->setResultName($content[3]);
        return $this;
    }

    public function serializeContents(): array {
        return [$this->getValue1(), $this->getOperator(), $this->getValue2(), $this->getResultName()];
    }

    public function getAddingVariables(): array {
        return [new DummyVariable($this->getResultName(), DummyVariable::STRING)];
    }
}