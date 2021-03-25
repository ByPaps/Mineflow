<?php

namespace aieuo\mineflow\ui;

use aieuo\mineflow\flowItem\FlowItem;
use aieuo\mineflow\flowItem\FlowItemContainer;
use aieuo\mineflow\formAPI\element\Button;
use aieuo\mineflow\formAPI\ListForm;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\utils\Session;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class FlowItemContainerForm {

    public function sendActionList(Player $player, FlowItemContainer $container, string $type, array $messages = []): void {
        $actions = $container->getItems($type);

        $buttons = [new Button("@form.back"), new Button("@{$type}.add")];
        foreach ($actions as $action) {
            $buttons[] = new Button(empty($action->getCustomName()) ? trim(TextFormat::clean($action->getDetail())) : $action->getCustomName());
        }

        (new ListForm(Language::get("form.{$type}Container.list.title", [$container->getContainerName()])))
            ->addButtons($buttons)
            ->onReceive(function (Player $player, int $data) use($container, $type, $actions) {
                if ($data === 0) {
                    if ($container instanceof Recipe) {
                        (new RecipeForm)->sendRecipeMenu($player, $container);
                    } else {
                        /** @var FlowItem $container */
                        (new FlowItemForm)->sendFlowItemCustomMenu($player, $container, $type);
                    }
                    return;
                }
                Session::getSession($player)
                    ->set("action_list_clicked", null)
                    ->push("parents", $container);

                if ($data === 1) {
                    (new FlowItemForm)->selectActionCategory($player, $container, $type);
                    return;
                }
                $data -= 2;
                $action = $actions[$data];
                Session::getSession($player)->set("action_list_clicked", $action);

                (new FlowItemForm)->sendAddedItemMenu($player, $container, $type, $action);
            })->addArgs($container, $actions)->addMessages($messages)->show($player);
    }

    public function sendMoveAction(Player $player, FlowItemContainer $container, string $type, int $selected, array $messages = [], int $count = 0): void {
        $actions = $container->getItems($type);
        $selectedAction = $actions[$selected];

        $buttons = [new Button("@form.back")];
        foreach ($actions as $i => $action) {
            $buttons[] = new Button(($i === $selected ? TextFormat::AQUA : "").trim(TextFormat::clean($action->getDetail())));
        }
        $buttons[] = new Button("");

        (new ListForm(Language::get("form.{$type}Container.move.title", [$container->getContainerName(), $selectedAction->getName()])))
            ->setContent("@form.{$type}Container.move.content")
            ->addButtons($buttons)
            ->onReceive(function (Player $player, int $data) use($container, $type, $selected, $actions, $count) {
                $move = $actions[$selected];
                if ($data === 0) {
                    (new FlowItemForm)->sendAddedItemMenu($player, $container, $type, $move, [$count === 0 ? "@form.cancelled" : "@form.moved"]);
                    return;
                }
                $data --;

                $actions = $this->getMovedContents($actions, $selected, $data);
                $container->setItems($actions, $type);
                $this->sendMoveAction($player, $container, $type, $selected < $data ? $data-1 : $data, ["@form.moved"], ++$count);
            })->addMessages($messages)->show($player);
    }

    public function getMovedContents(array $contents, int $from, int $to): array {
        $move = $contents[$from];
        if ($from < $to) $to--;
        unset($contents[$from]);
        $newContents = [];
        foreach (array_values($contents) as $i => $action) {
            if ($i === $to) $newContents[] = $move;
            $newContents[] = $action;
        }
        if (count($contents) === count($newContents)) $newContents[] = $move;
        return $newContents;
    }
}