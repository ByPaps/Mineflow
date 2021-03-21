<?php

namespace aieuo\mineflow\flowItem\condition;

use aieuo\mineflow\flowItem\FlowItem;
use aieuo\mineflow\formAPI\element\mineflow\ExampleInput;
use aieuo\mineflow\Main;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\utils\Language;

class ExistsVariable extends FlowItem implements Condition {

    protected $id = self::EXISTS_VARIABLE;

    protected $name = "condition.existsVariable.name";
    protected $detail = "condition.existsVariable.detail";
    protected $detailDefaultReplace = ["name"];

    protected $category = Category::VARIABLE;

    /** @var string */
    private $variableName;

    public function __construct(string $name = "") {
        $this->variableName = $name;
    }

    public function setVariableName(string $variableName): void {
        $this->variableName = $variableName;
    }

    public function getVariableName(): string {
        return $this->variableName;
    }

    public function isDataValid(): bool {
        return $this->variableName !== "";
    }

    public function getDetail(): string {
        if (!$this->isDataValid()) return $this->getName();
        return Language::get($this->detail, [$this->getVariableName()]);
    }

    public function execute(Recipe $source): \Generator {
        $this->throwIfCannotExecute();

        $helper = Main::getVariableHelper();
        $name = $source->replaceVariables($this->getVariableName());

        yield true;
        return $source->getVariable($name) !== null or $helper->get($name) !== null or $helper->getNested($name) !== null;
    }

    public function getEditFormElements(array $variables): array {
        return [
            new ExampleInput("@action.variable.form.name", "aieuo", $this->getVariableName(), true),
        ];
    }

    public function loadSaveData(array $content): FlowItem {
        $this->setVariableName($content[0]);
        return $this;
    }

    public function serializeContents(): array {
        return [$this->getVariableName()];
    }
}