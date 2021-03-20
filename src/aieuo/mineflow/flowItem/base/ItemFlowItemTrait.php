<?php


namespace aieuo\mineflow\flowItem\base;


use aieuo\mineflow\exception\InvalidFlowValueException;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\variable\object\ItemObjectVariable;
use pocketmine\item\Item;

trait ItemFlowItemTrait {

    /* @var string[] */
    private $itemVariableNames = [];

    public function getItemVariableName(string $name = ""): string {
        return $this->itemVariableNames[$name] ?? "";
    }

    public function setItemVariableName(string $item, string $name = ""): void {
        $this->itemVariableNames[$name] = $item;
    }

    public function getItem(Recipe $origin, string $name = ""): Item {
        $item = $origin->replaceVariables($rawName = $this->getItemVariableName($name));

        $variable = $origin->getVariable($item);
        if ($variable instanceof ItemObjectVariable and ($item = $variable->getItem()) instanceof Item) {
            return $item;
        }

        throw new InvalidFlowValueException($this->getName(), Language::get("action.target.not.valid", [["action.target.require.item"], $rawName]));
    }
}