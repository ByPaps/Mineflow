<?php

namespace aieuo\mineflow\flowItem\condition;

use aieuo\mineflow\economy\Economy;
use aieuo\mineflow\exception\InvalidFlowValueException;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\utils\Language;
use pocketmine\utils\TextFormat;

class TakeMoneyCondition extends TypeMoney {

    protected $id = self::TAKE_MONEY_CONDITION;

    protected $name = "condition.takeMoney.name";
    protected $detail = "condition.takeMoney.detail";

    public function execute(Recipe $source): \Generator {
        $this->throwIfCannotExecute();

        if (!Economy::isPluginLoaded()) {
            throw new InvalidFlowValueException(TextFormat::RED.Language::get("economy.notfound"));
        }

        $name = $source->replaceVariables($this->getPlayerName());
        $amount = $source->replaceVariables($this->getAmount());

        $this->throwIfInvalidNumber($amount, 1);

        $economy = Economy::getPlugin();
        $myMoney = $economy->getMoney($name);
        if ($myMoney >= $this->getAmount()) {
            $economy->takeMoney($name, (int)$amount);
            return true;
        }

        yield true;
        return false;
    }
}