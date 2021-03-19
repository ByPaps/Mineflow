<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\flowItem\base\EntityFlowItem;
use aieuo\mineflow\flowItem\base\EntityFlowItemTrait;
use aieuo\mineflow\flowItem\FlowItem;
use aieuo\mineflow\formAPI\element\mineflow\EntityVariableDropdown;
use aieuo\mineflow\formAPI\element\mineflow\ExampleNumberInput;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\utils\Language;

class SetScale extends FlowItem implements EntityFlowItem {
    use EntityFlowItemTrait;

    protected $id = self::SET_SCALE;

    protected $name = "action.setScale.name";
    protected $detail = "action.setScale.detail";
    protected $detailDefaultReplace = ["entity", "scale"];

    protected $category = Category::ENTITY;

    /** @var string */
    private $scale;

    public function __construct(string $entity = "", string $scale = "") {
        $this->setEntityVariableName($entity);
        $this->scale = $scale;
    }

    public function setScale(string $scale): void {
        $this->scale = $scale;
    }

    public function getScale(): string {
        return $this->scale;
    }

    public function isDataValid(): bool {
        return $this->getEntityVariableName() !== "" and $this->scale !== "";
    }

    public function getDetail(): string {
        if (!$this->isDataValid()) return $this->getName();
        return Language::get($this->detail, [$this->getEntityVariableName(), $this->getScale()]);
    }

    public function execute(Recipe $origin): \Generator {
        $this->throwIfCannotExecute();

        $health = $origin->replaceVariables($this->getScale());

        $this->throwIfInvalidNumber($health, 0, null);

        $entity = $this->getEntity($origin);
        $this->throwIfInvalidEntity($entity);

        $entity->setScale((float)$health);
        yield true;
    }

    public function getEditFormElements(array $variables): array {
        return [
            new EntityVariableDropdown($variables, $this->getEntityVariableName()),
            new ExampleNumberInput("@action.setScale.form.scale", "1", $this->getScale(), true, 0, null, [0]),
        ];
    }

    public function loadSaveData(array $content): FlowItem {
        $this->setEntityVariableName($content[0]);
        $this->setScale($content[1]);
        return $this;
    }

    public function serializeContents(): array {
        return [$this->getEntityVariableName(), $this->getScale()];
    }
}