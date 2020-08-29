<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\flowItem\base\PositionFlowItem;
use aieuo\mineflow\flowItem\base\PositionFlowItemTrait;
use aieuo\mineflow\formAPI\Form;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\formAPI\element\Label;
use aieuo\mineflow\formAPI\element\Input;
use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\formAPI\element\Toggle;
use aieuo\mineflow\variable\NumberVariable;

class GetDistance extends Action implements PositionFlowItem {
    use PositionFlowItemTrait;

    protected $id = self::GET_DISTANCE;

    protected $name = "action.getDistance.name";
    protected $detail = "action.getDistance.detail";
    protected $detailDefaultReplace = ["pos1", "pos2", "result"];

    protected $category = Category::LEVEL;

    protected $targetRequired = Recipe::TARGET_REQUIRED_NONE;
    protected $returnValueType = self::RETURN_VARIABLE_VALUE;

    /** @var string */
    private $resultName;
    /* @var string */
    private $lastResult;

    public function __construct(string $pos1 = "pos1", string $pos2 = "pos2", string $result = "distance") {
        $this->setPositionVariableName($pos1, "pos1");
        $this->setPositionVariableName($pos2, "pos2");
        $this->resultName = $result;
    }

    public function setResultName(string $resultName): void {
        $this->resultName = $resultName;
    }

    public function getResultName(): string {
        return $this->resultName;
    }

    public function isDataValid(): bool {
        return $this->getPositionVariableName("pos1") !== "" and $this->getPositionVariableName("pos2") !== "" and $this->resultName !== "";
    }

    public function getDetail(): string {
        if (!$this->isDataValid()) return $this->getName();
        return Language::get($this->detail, [$this->getPositionVariableName("pos1"), $this->getPositionVariableName("pos2"), $this->getResultName()]);
    }

    public function execute(Recipe $origin): bool {
        $this->throwIfCannotExecute();

        $pos1 = $this->getPosition($origin, "pos1");
        $this->throwIfInvalidPosition($pos1);
        $pos2 = $this->getPosition($origin, "pos2");
        $this->throwIfInvalidPosition($pos2);
        $result = $origin->replaceVariables($this->getResultName());

        $distance = $pos1->distance($pos2);

        $this->lastResult = (string)$distance;
        $origin->addVariable(new NumberVariable($distance, $result));
        return true;
    }

    public function getEditForm(array $default = [], array $errors = []): Form {
        return (new CustomForm($this->getName()))
            ->setContents([
                new Label($this->getDescription()),
                new Input("@action.getDistance.form.pos1", Language::get("form.example", ["pos1"]), $default[1] ?? $this->getPositionVariableName("pos1")),
                new Input("@action.getDistance.form.pos2", Language::get("form.example", ["pos2"]), $default[2] ?? $this->getPositionVariableName("pos2")),
                new Input("@flowItem.form.resultVariableName", Language::get("form.example", ["distance"]), $default[3] ?? $this->getResultName()),
                new Toggle("@form.cancelAndBack")
            ])->addErrors($errors);
    }

    public function parseFromFormData(array $data): array {
        $errors = [];
        if ($data[1] === "") $errors[] = ["@form.insufficient", 1];
        if ($data[2] === "") $errors[] = ["@form.insufficient", 2];
        if ($data[3] === "") $errors[] = ["@form.insufficient", 3];
        return ["contents" => [$data[1], $data[2], $data[3]], "cancel" => $data[4], "errors" => $errors];
    }

    public function loadSaveData(array $content): Action {
        if (!isset($content[2])) throw new \OutOfBoundsException();
        $this->setPositionVariableName($content[0], "pos1");
        $this->setPositionVariableName($content[1], "pos2");
        $this->setResultName($content[2]);
        return $this;
    }

    public function serializeContents(): array {
        return [$this->getPositionVariableName("pos1"), $this->getPositionVariableName("pos2"), $this->getResultName()];
    }

    public function getReturnValue(): string {
        return $this->lastResult;
    }
}