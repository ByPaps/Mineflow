<?php


namespace aieuo\mineflow\flowItem\base;


use aieuo\mineflow\exception\InvalidFlowValueException;
use aieuo\mineflow\recipe\Recipe;
use pocketmine\item\Item;

interface ItemFlowItem {

    public function getItemVariableName(string $name = ""): string;

    public function setItemVariableName(string $item, string $name = ""): void;

    /**
     * @param Recipe $source
     * @param string $name
     * @return Item
     * @throws InvalidFlowValueException
     */
    public function getItem(Recipe $source, string $name = ""): Item;
}