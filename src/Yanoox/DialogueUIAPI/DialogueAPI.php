<?php

/*

 _____      _____       ____ _____   ______          _____           _____
|\    \    /    /| ____|\   \\    \ |\     \    ____|\    \     ____|\    \  _____      _____
| \    \  /    / |/    /\    \\    \| \     \  /     /\    \   /     /\    \ \    \    /    /
|  \____\/    /  /    |  |    \|    \  \     |/     /  \    \ /     /  \    \ \    \  /    /
 \ |    /    /  /|    |__|    ||     \  |    |     |    |    |     |    |    | \____\/____/
  \|___/    /  / |    .--.    ||      \ |    |     |    |    |     |    |    | /    /\    \
      /    /  /  |    |  |    ||    |\ \|    |\     \  /    /|\     \  /    /|/    /  \    \
     /____/  /   |____|  |____||____||\_____/| \_____\/____/ | \_____\/____/ /____/ /\ \____\
    |`    | /    |    |  |    ||    |/ \|   ||\ |    ||    | /\ |    ||    | /    |/  \|    |
    |_____|/     |____|  |____||____|   |___|/ \|____||____|/  \|____||____|/|____|    |____|
       )/          \(      )/    \(       )/      \(    )/        \(    )/     \(        )/
       '            '      '      '       '        '    '          '    '       '        '

*/
namespace Yanoox\DialogueUIAPI;

use function json_encode;
use function array_map;
use InvalidArgumentException;
use pocketmine\entity\Entity;
use Yanoox\DialogueUIAPI\Listener\PacketHandler;
use pocketmine\plugin\PluginBase;
use Yanoox\DialogueUIAPI\data\DialoguePoolData;
use Yanoox\DialogueUIAPI\element\DialogueButton;
use pocketmine\network\mcpe\protocol\NpcDialoguePacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;

final class DialogueAPI{

	protected ?int $actorId = null;

    /**
     * @param string $sceneName
     * @param string $npcName
     * @param int $npcId
     * @param string $dialogue
     * @param DialogueButton[] $buttons
     */
    public function __construct(protected string $sceneName, protected string $npcName, protected int $npcId, protected string $dialogue, protected array $buttons)
    {
        if($this->sceneName === "")  throw new InvalidArgumentException("scenename should not be empty.");
    }

    public static function register(PluginBase $plugin)
    {
        $plugin->getServer()->getPluginManager()->registerEvents(new PacketHandler(), $plugin);
    }

    public static function create(string $sceneName, string $npcName, int $npcId, string $dialogue, array $buttons): self
    {
        if ($sceneName === '') throw new \InvalidArgumentException("sceneName should not be");
        return new self($sceneName, $npcName, $npcId, $dialogue, $buttons);
    }

    /**
     * TODO: if entity is null
     * @param Player[] $players
     * @param Entity $entity
     * @return void
     */
	public function displayTo(array $players, Entity $entity) : void
    {
        $this->actorId = $entity->getId();
        $propertyManager = $entity->getNetworkProperties();
        $pk = NpcDialoguePacket::create(
            $this->actorId,
            NpcDialoguePacket::ACTION_OPEN,
            $this->dialogue,
            $this->sceneName,
            $this->npcName,
            json_encode(array_map(static fn(DialogueButton $data) => $data->dump(), $this->buttons))
        );
        foreach ($players as $player) {
            $propertyManager->setByte(EntityMetadataProperties::HAS_NPC_COMPONENT, 1);
            $propertyManager->setString(EntityMetadataProperties::INTERACTIVE_TAG, $this->dialogue);
            $propertyManager->setString(EntityMetadataProperties::NPC_ACTIONS, json_encode(array_map(static fn(DialogueButton $data) => $data->dump(), $this->buttons)));
            $propertyManager->setString(EntityMetadataProperties::NPC_SKIN_INDEX, json_encode([
                "picker_offsets" => [
                    "scale" => [0, 0, 0],
                    "translate" => [0, 0, 0],
                ],
                "portrait_offsets" => [
                    "scale" => [1, 1, 1],
                    "translate" => [0, 0, 0]
                ]
            ]));
            $player->getNetworkSession()->sendDataPacket($pk);
            DialoguePoolData::$queue[$player->getUniqueId()->toString()][$this->sceneName] = $this;
        }
    }


	public function onClose(Player $player) : void{
		$mappedActions = json_encode(array_map(static fn(DialogueButton $data) => $data->dump(), $this->buttons));
		$player->getNetworkSession()->sendDataPacket(
			NpcDialoguePacket::create(
				$this->actorId,
				NpcDialoguePacket::ACTION_CLOSE,
				$this->dialogue,
				$this->sceneName,
				$this->npcName,
				$mappedActions
			)
		);
	}

    public function onClick(Player $player, int $id): void
    {
        if(!array_key_exists($id, $this->buttons)){
            throw new InvalidArgumentException("ID $id doesn't exist");
        }
        $button = $this->buttons[$id];

        if($button->getCloseOnClick())$this->onClose($player);

        ($this->buttons[$id]->getHandler())($player, $button->getName());
    }

    public function setActorId(int $actorId): void
    {
        $this->actorId = $actorId;
    }

    public function getActorId(): int
    {
        return $this->actorId;
    }
}
