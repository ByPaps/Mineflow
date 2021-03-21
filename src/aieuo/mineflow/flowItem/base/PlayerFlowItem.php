<?php


namespace aieuo\mineflow\flowItem\base;


use aieuo\mineflow\exception\InvalidFlowValueException;
use aieuo\mineflow\recipe\Recipe;
use pocketmine\Player;

interface PlayerFlowItem {

    public function getPlayerVariableName(string $name = ""): string;

    public function setPlayerVariableName(string $player, string $name = ""): void;

    /**
     * @param Recipe $source
     * @param string $name
     * @return Player
     * @throws InvalidFlowValueException
     */
    public function getPlayer(Recipe $source, string $name): Player;

    public function throwIfInvalidPlayer(Player $player): void;
}