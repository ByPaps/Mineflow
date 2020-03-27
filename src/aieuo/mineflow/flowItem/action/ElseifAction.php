<?php

namespace aieuo\mineflow\flowItem\action;

use pocketmine\entity\Entity;
use aieuo\mineflow\recipe\Recipe;

class ElseifAction extends IFAction {

    protected $id = self::ACTION_ELSEIF;

    protected $name = "action.elseif.name";
    protected $detail = "action.elseif.description";

    public function getDetail(): string {
        $details = ["=============elseif============="];
        foreach ($this->getConditions() as $condition) {
            $details[] = $condition->getDetail();
        }
        $details[] = "~~~~~~~~~~~~~~~~~~~~~~~~~~~";
        foreach ($this->getActions() as $action) {
            $details[] = $action->getDetail();
        }
        $details[] = "================================";
        return implode("\n", $details);
    }

    public function execute(?Entity $target, Recipe $origin): bool {
        $lastResult = $origin->getLastActionResult();
        if ($lastResult === null) throw new \UnexpectedValueException();
        if ($lastResult) return false;

        $matched = true;
        foreach ($this->getConditions() as $condition) {
            $result = $condition->execute($target, $origin);
            if (!$result) $matched = false;
        }
        if (!$matched) return false;

        foreach ($this->getActions() as $action) {
            $action->execute($target, $origin);
        }
        return true;
    }
}