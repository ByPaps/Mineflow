<?php

namespace aieuo\mineflow\flowItem\condition;

use aieuo\mineflow\flowItem\FlowItem;
use aieuo\mineflow\flowItem\FlowItemExecutor;
use aieuo\mineflow\formAPI\element\mineflow\ExampleInput;
use aieuo\mineflow\Main;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\utils\Language;

class ExistsConfigFile extends FlowItem implements Condition {

    protected $id = self::EXISTS_CONFIG_FILE;

    protected $name = "condition.existsConfigFile.name";
    protected $detail = "condition.existsConfigFile.detail";
    protected $detailDefaultReplace = ["name"];

    protected $category = Category::SCRIPT;

    /** @var string */
    private $fileName;

    public function __construct(string $name = "") {
        $this->fileName = $name;
    }

    public function setFileName(string $name): self {
        $this->fileName = $name;
        return $this;
    }

    public function getFileName(): string {
        return $this->fileName;
    }

    public function isDataValid(): bool {
        return $this->getFileName() !== "";
    }

    public function getDetail(): string {
        if (!$this->isDataValid()) return $this->getName();
        return Language::get($this->detail, [$this->getFileName()]);
    }

    public function execute(FlowItemExecutor $source): \Generator {
        $this->throwIfCannotExecute();

        $name = $source->replaceVariables($this->getFileName());
        $name = preg_replace("#[.¥/:?<>|*\"]#u", "", preg_quote($name, "/@#~"));

        yield true;
        return file_exists(Main::getInstance()->getDataFolder()."/configs/".$name.".yml");
    }

    public function getEditFormElements(array $variables): array {
        return [
            new ExampleInput("@action.createConfigVariable.form.name", "config", $this->getFileName(), true),
        ];
    }

    public function parseFromFormData(array $data): array {
        $errors = [];
        if (preg_match("#[.¥/:?<>|*\"]#u", preg_quote($data[0], "/@#~"))) $errors = ["@form.recipe.invalidName", 0];
        return ["contents" => [$data[0]], "errors" => $errors];
    }

    public function loadSaveData(array $content): FlowItem {
        $this->setFileName($content[0]);
        return $this;
    }

    public function serializeContents(): array {
        return [$this->getFileName()];
    }
}