<?php

namespace nitf\pmmp\deathmatch\game\event;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent as BreakEvent;
use nitf\pmmp\deathmatch\member\MemberRepository;

class BlockBreakEvent implements Listener{

    public function event(BreakEvent $event): void{
        $player = $event->getPlayer();
        if (MemberRepository::isMember($player)){
            $member = MemberRepository::getMember($player);
            $arena = $member->getArena();
            if ($arena->getConfig()->get('protect-world')){
                $event->setCancelled();
            }
        }
    }
}
