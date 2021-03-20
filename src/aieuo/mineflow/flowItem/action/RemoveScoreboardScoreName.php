<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\flowItem\base\ScoreboardFlowItem;
use aieuo\mineflow\flowItem\base\ScoreboardFlowItemTrait;
use aieuo\mineflow\flowItem\FlowItem;
use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\formAPI\element\Label;
use aieuo\mineflow\formAPI\element\mineflow\ExampleNumberInput;
use aieuo\mineflow\formAPI\element\mineflow\ScoreboardVariableDropdown;
use aieuo\mineflow\formAPI\element\Toggle;
use aieuo\mineflow\formAPI\Form;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\utils\Language;

class RemoveScoreboardScoreName extends FlowItem implements ScoreboardFlowItem {
    use ScoreboardFlowItemTrait;

    protected $id = self::REMOVE_SCOREBOARD_SCORE_NAME;

    protected $name = "action.removeScoreName.name";
    protected $detail = "action.removeScoreName.detail";
    protected $detailDefaultReplace = ["scoreboard", "score"];

    protected $category = Category::SCOREBOARD;

    /* @var string */
    private $score;

    public function __construct(string $scoreboard = "", string $score = "") {
        $this->setScoreboardVariableName($scoreboard);
        $this->score = $score;
    }

    public function getScore(): string {
        return $this->score;
    }

    public function setScore(string $score): void {
        $this->score = $score;
    }

    public function getDetail(): string {
        if (!$this->isDataValid()) return $this->getName();
        return Language::get($this->detail, [$this->getScoreboardVariableName(), $this->getScore()]);
    }

    public function isDataValid(): bool {
        return $this->getScoreboardVariableName() !== "" and $this->getScore() !== "";
    }

    public function execute(Recipe $origin): \Generator {
        $this->throwIfCannotExecute();

        $score = $origin->replaceVariables($this->getScore());

        $this->throwIfInvalidNumber($score);

        $board = $this->getScoreboard($origin);

        $board->removeScoreName((int)$score);
        yield true;
    }

    public function getEditFormElements(array $variables): array {
        return [
            new ScoreboardVariableDropdown($variables, $this->getScoreboardVariableName()),
            new ExampleNumberInput("@action.setScore.form.score", "100", $this->getScore(), true),
        ];
    }

    public function loadSaveData(array $content): FlowItem {
        $this->setScoreboardVariableName($content[0]);
        $this->setScore($content[1]);
        return $this;
    }

    public function serializeContents(): array {
        return [$this->getScoreboardVariableName(), $this->getScore()];
    }
}
