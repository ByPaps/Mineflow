<?php

namespace aieuo\mineflow\action\process;

use pocketmine\entity\Entity;
use pocketmine\Server;
use pocketmine\Player;
use aieuo\mineflow\variable\NumberVariable;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\utils\Categories;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\formAPI\element\Label;
use aieuo\mineflow\formAPI\element\Input;
use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\action\process\Process;
use aieuo\mineflow\FormAPI\element\Toggle;

class GetE extends Process {

    protected $id = self::GET_E;

    protected $name = "@action.getE.name";
    protected $description = "@action.getE.description";
    protected $detail = "@action.getE.detail";

    protected $category = Categories::CATEGORY_ACTION_CALCULATION;

    protected $targetRequired = Recipe::TARGET_REQUIRED_NONE;

    /** @var string */
    private $resultName = "e";

    public function __construct(string $result = "e") {
        $this->resultName = $result;
    }

    public function setResultName(string $name): self {
        $this->resultName = $name;
        return $this;
    }

    public function getResultName(): string {
        return $this->resultName;
    }

    public function isDataValid(): bool {
        return !empty($this->getResultName());
    }

    public function execute(?Entity $target, ?Recipe $origin = null): ?bool {
        if (!$this->isDataValid()) {
            if ($target instanceof Player) $target->sendMessage(Language::get("invalid.contents", [$this->getName()]));
            else Server::getInstance()->getLogger()->info(Language::get("invalid.contents", [$this->getName()]));
            return null;
        }
        if (!($origin instanceof Recipe)) {
            $target->sendMessage(Language::get("action.error", [Language::get("action.error.recipe"), $this->getName()]));
            return null;
        }

        $resultName = $origin->replaceVariables($this->getResultName());
        $origin->addVariable(new NumberVariable($resultName, M_E));
        return false;
    }

    public function getEditForm(array $default = [], array $errors = []) {
        return (new CustomForm($this->getName()))
            ->setContents([
                new Label($this->getDescription()),
                new Input("@action.calculate.form.result", Language::get("form.example", ["e"]), $default[1] ?? $this->getResultName()),
                new Toggle("@form.cancelAndBack")
            ])->addErrors($errors);
    }

    public function parseFromFormData(array $data): array {
        $status = true;
        $errors = [];
        if ($data[1] === "") {
            $status = false;
            $errors[] = ["@form.insufficient", 1];
        }
        return ["status" => $status, "contents" => [$data[1]], "cancel" => $data[2], "errors" => $errors];
    }

    public function parseFromSaveData(array $content): ?Process {
        return $this;
    }

    public function serializeContents(): array {
        return [];
    }
}