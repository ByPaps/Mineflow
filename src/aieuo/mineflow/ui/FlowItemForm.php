<?php

namespace aieuo\mineflow\ui;

use aieuo\mineflow\exception\FlowItemLoadException;
use aieuo\mineflow\exception\InvalidFormValueException;
use aieuo\mineflow\flowItem\FlowItem;
use aieuo\mineflow\flowItem\FlowItemContainer;
use aieuo\mineflow\flowItem\FlowItemFactory;
use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\formAPI\element\Button;
use aieuo\mineflow\formAPI\element\CancelToggle;
use aieuo\mineflow\formAPI\element\Input;
use aieuo\mineflow\formAPI\element\Label;
use aieuo\mineflow\formAPI\Form;
use aieuo\mineflow\formAPI\ListForm;
use aieuo\mineflow\formAPI\ModalForm;
use aieuo\mineflow\Main;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\utils\Session;
use pocketmine\Player;

class FlowItemForm {

    public function sendAddedItemMenu(Player $player, FlowItemContainer $container, string $type, FlowItem $action, array $messages = []): void {
        if ($action->hasCustomMenu()) {
            $action->sendCustomMenu($player);
            return;
        }
        /** @var Recipe|FlowItem $container */
        (new ListForm(Language::get("form.$type.addedItemMenu.title", [$container->getContainerName(), $action->getName()])))
            ->setContent(trim($action->getDetail()))
            ->addButtons([
                new Button("@form.back"),
                new Button("@form.edit"),
                new Button("@form.move"),
                new Button("@form.delete"),
            ])->onReceive(function (Player $player, int $data) use($container, $type, $action) {
                switch ($data) {
                    case 0:
                        Session::getSession($player)->pop("parents");
                        (new FlowItemContainerForm)->sendActionList($player, $container, $type);
                        break;
                    case 1:
                        $parents = Session::getSession($player)->get("parents");
                        $recipe = array_shift($parents);
                        $variables = $recipe->getAddingVariablesBefore($action, $parents, $type);
                        $form = new CustomForm($action->getName());
                        $form->addContent(new Label($action->getDescription()));
                        $form->addContents($action->getEditFormElements($variables));
                        $form->addContent(new CancelToggle());
                        $form->addArgs($form, $action, function ($result) use ($player, $container, $type, $action) {
                            $this->sendAddedItemMenu($player, $container, $type, $action, [$result ? "@form.changed" : "@form.cancelled"]);
                        })->onReceive([$this, "onUpdateAction"])->show($player);
                        break;
                    case 2:
                        (new FlowItemContainerForm)->sendMoveAction($player, $container, $type, array_search($action, $container->getItems($type), true));
                        break;
                    case 3:
                        $this->sendConfirmDelete($player, $action, $container, $type);
                        break;
                }
            })->addMessages($messages)->show($player);
    }

    public function onUpdateAction(Player $player, ?array $data, Form $form, FlowItem $action, callable $callback): void {
        if ($data === null) return;

        array_shift($data);
        $cancelChecked = array_pop($data);

        if ($cancelChecked) {
            $callback(false);
            return;
        }

        try {
            $values = $action->parseFromFormData($data);
        } catch (InvalidFormValueException $e) {
            $form->resend([[$e->getMessage(), $e->getIndex() + 1]]);
            return;
        }

        try {
            $action->loadSaveData($values);
        } catch (FlowItemLoadException|\ErrorException $e) {
            $player->sendMessage(Language::get("action.error.recipe"));
            Main::getInstance()->getLogger()->logException($e);
            return;
        }
        $callback(true);
    }

    public function selectActionCategory(Player $player, FlowItemContainer $container, string $type): void {
        $buttons = [
            new Button("@form.back", function () use($player, $container, $type) {
                Session::getSession($player)->pop("parents");
                (new FlowItemContainerForm)->sendActionList($player, $container, $type);
            }),
            new Button("@form.items.category.favorite", function () use($player, $container, $type) {
                $favorites = Main::getInstance()->getPlayerSettings()->getFavorites($player->getName(), $type);
                $actions = [];
                foreach ($favorites as $favorite) {
                    $action = FlowItemFactory::get($favorite);
                    if ($action === null) continue;

                    $actions[] = $action;
                }
                Session::getSession($player)->set("flowItem_category", Language::get("form.items.category.favorite"));
                $this->sendSelectAction($player, $container, $type, $actions);
            })
        ];

        foreach (Category::getCategories() as $category) {
            $buttons[] = new Button("@category.".$category, function () use($player, $container, $type, $category) {
                $isCondition = $type === FlowItemContainer::CONDITION;
                $actions = FlowItemFactory::getByFilter($category, Main::getInstance()->getPlayerSettings()->getNested($player->getName().".permission", 0), !$isCondition, $isCondition);

                Session::getSession($player)->set("flowItem_category", Language::get("category.".$category));
                $this->sendSelectAction($player, $container, $type, $actions);
            });
        }

        $buttons[] = new Button("@form.search", function () use($player, $container, $type) {
            $this->sendSearchAction($player, $container, $type);
        });

        /** @var Recipe|FlowItem $container */
        (new ListForm(Language::get("form.$type.category.title", [$container->getContainerName()])))
            ->addButtons($buttons)
            ->show($player);
    }

    public function sendSearchAction(Player $player, FlowItemContainer $container, string $type): void {
        (new CustomForm(Language::get("form.{$type}.search.title", [$container->getContainerName()])))
            ->setContents([
                new Input("@form.items.search.keyword", "", Session::getSession($player)->get("flowItem_search", ""), true),
                new CancelToggle(function () use($player, $container, $type) { $this->selectActionCategory($player, $container, $type); })
            ])->onReceive(function (Player  $player, array $data) use($container, $type) {
                $isCondition = $type === FlowItemContainer::CONDITION;
                $permission = Main::getInstance()->getPlayerSettings()->getNested($player->getName().".permission", 0);
                $actions = array_values(array_filter(FlowItemFactory::getByFilter(null, $permission, !$isCondition, $isCondition), function (FlowItem  $item) use($data) {
                    return stripos($item->getName(), $data[0]) !== false;
                }));

                Session::getSession($player)->set("flowItem_search", $data[0]);
                Session::getSession($player)->set("flowItem_category", Language::get("form.items.category.search", [$data[0]]));
                $this->sendSelectAction($player, $container, $type, $actions);
            })->show($player);
    }

    public function sendSelectAction(Player $player, FlowItemContainer $container, string $type, array $items): void {
        $buttons = [
            new Button("@form.back", function () use($player, $container, $type) { $this->selectActionCategory($player, $container, $type); })
        ];
        foreach ($items as $item) {
            $buttons[] = new Button($item->getName());
        }
        /** @var Recipe|FlowItem $container */
        (new ListForm(Language::get("form.$type.select.title", [$container->getContainerName(), Session::getSession($player)->get("flowItem_category", "")])))
            ->setContent(count($buttons) === 1 ? "@form.action.empty" : "@form.selectButton")
            ->addButtons($buttons)
            ->onReceive(function (Player $player, int $data) use($container, $type, $items) {
                $data --;

                Session::getSession($player)->set($type."s", $items);
                $item = clone $items[$data];
                $this->sendActionMenu($player, $container, $type, $item);
            })->show($player);
    }

    public function sendActionMenu(Player $player, FlowItemContainer $container, string $type, FlowItem $item, array $messages = []): void {
        $favorites = Main::getInstance()->getPlayerSettings()->getFavorites($player->getName(), $type);

        /** @var Recipe|FlowItem $container */
        (new ListForm(Language::get("form.$type.menu.title", [$container->getContainerName(), $item->getId()])))
            ->setContent($item->getDescription())
            ->addButtons([
                new Button("@form.back"),
                new Button("@form.add"),
                new Button(in_array($item->getId(), $favorites, true) ? "@form.items.removeFavorite" : "@form.items.addFavorite"),
            ])->onReceive(function (Player $player, int $data) use($container, $type, $item) {
                switch ($data) {
                    case 0:
                        $actions = Session::getSession($player)->get($type."s");
                        $this->sendSelectAction($player, $container, $type, $actions);
                        break;
                    case 1:
                        if ($item->hasCustomMenu()) {
                            $container->addItem($item, $type);
                            $item->sendCustomMenu($player);
                            return;
                        }

                        $parents = Session::getSession($player)->get("parents");
                        $recipe = array_shift($parents);
                        $variables = $recipe->getAddingVariablesBefore($item, $parents, $type);
                        $form = new CustomForm($item->getName());
                        $form->addContent(new Label($item->getDescription()));
                        $form->addContents($item->getEditFormElements($variables));
                        $form->addContent(new CancelToggle());
                        $form->addArgs($form, $item, function ($result) use ($player, $container, $type, $item) {
                            if ($result) {
                                $container->addItem($item, $type);
                                Session::getSession($player)->pop("parents");
                                (new FlowItemContainerForm)->sendActionList($player, $container, $type, ["@form.added"]);
                            } else {
                                $this->sendActionMenu($player, $container, $type, $item, ["@form.cancelled"]);
                            }
                        })->onReceive([new FlowItemForm(), "onUpdateAction"])->show($player);
                        break;
                    case 2:
                        $config = Main::getInstance()->getPlayerSettings();
                        $config->toggleFavorite($player->getName(), $type, $item->getId());
                        $config->save();
                        $this->sendActionMenu($player, $container, $type, $item, ["@form.changed"]);
                        break;
                }
            })->addMessages($messages)->show($player);
    }

    public function sendConfirmDelete(Player $player, FlowItem $action, FlowItemContainer $container, string $type): void {
        (new ModalForm(Language::get("form.items.delete.title", [$container->getContainerName(), $action->getName()])))
            ->setContent(Language::get("form.delete.confirm", [trim($action->getDetail())]))
            ->onYes(function() use ($player, $action, $container, $type) {
                $index = array_search($action, $container->getItems($type), true);
                $container->removeItem($index, $type);
                Session::getSession($player)->pop("parents");
                (new FlowItemContainerForm)->sendActionList($player, $container, $type, ["@form.deleted"]);
            })->onNo(function() use ($player, $action, $container, $type) {
                if ($container instanceof FlowItem and $container->hasCustomMenu()) {
                    $container->sendCustomMenu($player, ["@form.cancelled"]);
                } else {
                    $this->sendAddedItemMenu($player, $container, $type, $action, ["@form.cancelled"]);
                }
            })->show($player);
    }

    public function sendChangeName(Player $player, FlowItem $item, FlowItemContainer $container, string $type): void {
        (new CustomForm(Language::get("form.recipe.changeName.title", [$item->getName()])))
            ->setContents([
                new Input("@form.recipe.changeName.content1", "", $item->getCustomName()),
                new CancelToggle()
            ])->onReceive(function (Player $player, array $data) use($item, $container, $type) {
                if ($data[1]) {
                    if ($container instanceof FlowItem and $container->hasCustomMenu()) {
                        $container->sendCustomMenu($player, ["@form.cancelled"]);
                    } else {
                        (new FlowItemForm)->sendAddedItemMenu($player, $container, $type, $item, ["@form.cancelled"]);
                    }
                    return;
                }

                $item->setCustomName($data[0]);
                if ($container instanceof FlowItem and $container->hasCustomMenu()) {
                    $container->sendCustomMenu($player, ["@form.changed"]);
                } else {
                    (new FlowItemForm)->sendAddedItemMenu($player, $container, $type, $item, ["@form.changed"]);
                }
            })->addArgs($item, $container)->show($player);
    }
}