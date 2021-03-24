<?php

namespace aieuo\mineflow\trigger\event;

use aieuo\mineflow\variable\DefaultVariables;
use aieuo\mineflow\variable\DummyVariable;
use aieuo\mineflow\variable\object\LocationObjectVariable;
use pocketmine\event\player\PlayerMoveEvent;

class PlayerMoveEventTrigger extends PlayerEventTrigger {
    public function __construct(string $subKey = "") {
        parent::__construct(PlayerMoveEvent::class, $subKey);
    }

    public function getVariables($event): array {
        /** @var PlayerMoveEvent $event */
        $variables = [
            "move_from" => new LocationObjectVariable($event->getFrom()),
            "move_to" => new LocationObjectVariable($event->getTo())
        ];
        $target = $event->getPlayer();
        return array_merge($variables, DefaultVariables::getPlayerVariables($target));
    }

    public function getVariablesDummy(): array {
        return [
            "move_from" => new DummyVariable("move_from", DummyVariable::LOCATION),
            "move_to" => new DummyVariable("move_to", DummyVariable::LOCATION),
            "target" => new DummyVariable("target", DummyVariable::PLAYER),
        ];
    }
}