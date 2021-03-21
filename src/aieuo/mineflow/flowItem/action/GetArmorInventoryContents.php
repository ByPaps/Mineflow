<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\flowItem\base\PlayerFlowItemTrait;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\variable\ListVariable;
use aieuo\mineflow\variable\object\ItemObjectVariable;
use pocketmine\item\Item;

class GetArmorInventoryContents extends GetInventoryContents {
    use PlayerFlowItemTrait;

    protected $id = self::GET_ARMOR_INVENTORY_CONTENTS;

    protected $name = "action.getArmorInventory.name";
    protected $detail = "action.getArmorInventory.detail";


    public function execute(Recipe $source): \Generator {
        $this->throwIfCannotExecute();

        $resultName = $source->replaceVariables($this->getResultName());

        $entity = $this->getPlayer($source);
        $this->throwIfInvalidPlayer($entity);

        $variable = new ListVariable(array_map(function (Item $item) {
            return new ItemObjectVariable($item);
        }, $entity->getArmorInventory()->getContents()), $resultName);

        $source->addVariable($variable);
        yield true;
        return $this->getResultName();
    }
}