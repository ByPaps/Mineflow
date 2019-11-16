<?php

namespace aieuo\mineflow\condition;

use pocketmine\entity\Entity;
use pocketmine\Player;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\condition\TypeItem;

class CanAddItem extends TypeItem {

    protected $id = self::CAN_ADD_ITEM;

    protected $name = "@condition.canAddItem.name";
    protected $description = "@condition.canAddItem.description";
    protected $detail = "condition.canAddItem.detail";

    public function execute(?Entity $target, ?Recipe $origin = null): ?bool {
        if (!($target instanceof Player)) return null;

        if (!$this->isDataValid()) {
            $target->sendMessage(Language::get("invalid.contents", [$this->getName()]));
            return null;
        }

        $item = $this->getItem();
        return $target->getInventory()->canAddItem($item);
    }
}