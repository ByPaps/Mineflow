<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\formAPI\element\Toggle;
use aieuo\mineflow\formAPI\Form;
use aieuo\mineflow\variable\StringVariable;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\formAPI\element\Label;
use aieuo\mineflow\formAPI\element\Input;
use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\Main;

class GetDate extends Action {

    protected $id = self::GET_DATE;

    protected $name = "action.getDate.name";
    protected $detail = "action.getDate.detail";
    protected $detailDefaultReplace = ["format", "result"];

    protected $category = Category::COMMON;

    protected $targetRequired = Recipe::TARGET_REQUIRED_NONE;
    protected $returnValueType = self::RETURN_VARIABLE_VALUE;

    /** @var string */
    private $format;
    /** @var string */
    private $resultName;
    /** @var string */
    private $lastResult = "";

    public function __construct(string $format = "H:i:s", string $resultName = "date") {
        $this->setFormat($format);
        $this->setResultName($resultName);
    }

    public function setFormat(string $format): void {
        $this->format = $format;
    }

    public function getFormat(): string {
        return $this->format;
    }

    public function setResultName(string $resultName): void {
        $this->resultName = $resultName;
    }

    public function getResultName(): string {
        return $this->resultName;
    }

    public function isDataValid(): bool {
        return $this->getFormat() !== "" and $this->getResultName();
    }

    public function getDetail(): string {
        if (!$this->isDataValid()) return $this->getName();
        return Language::get($this->detail, [$this->getFormat(), $this->getResultName()]);
    }

    public function execute(Recipe $origin): bool {
        $this->throwIfCannotExecute();

        $format = $origin->replaceVariables($this->getFormat());
        $resultName = $origin->replaceVariables($this->getResultName());

        $date = date($format);
        $this->lastResult = $date;
        $origin->addVariable(new StringVariable($date, $resultName));
        return true;
    }

    public function getEditForm(array $default = [], array $errors = []): Form {
        return (new CustomForm($this->getName()))
            ->setContents([
                new Label($this->getDescription()),
                new Input("@action.getDate.form.format", Language::get("form.example", ["H:i:s"]), $default[1] ?? $this->getFormat()),
                new Input("@flowItem.form.resultVariableName", Language::get("form.example", ["date"]), $default[2] ?? $this->getResultName()),
                new Toggle("@form.cancelAndBack")
            ])->addErrors($errors);
    }

    public function parseFromFormData(array $data): array {
        $errors = [];
        if ($data[1] === "") $errors[] = ["@form.insufficient", 1];
        if ($data[2] === "") $errors[] = ["@form.insufficient", 2];
        return ["contents" => [$data[1], $data[2]], "cancel" => $data[3], "errors" => $errors];
    }

    public function loadSaveData(array $content): Action {
        if (!isset($content[1])) throw new \OutOfBoundsException();

        $this->setFormat($content[0]);
        $this->setResultName($content[1]);
        return $this;
    }

    public function serializeContents(): array {
        return [$this->getFormat(), $this->getResultName()];
    }

    public function getReturnValue(): string {
        return $this->lastResult;
    }
}