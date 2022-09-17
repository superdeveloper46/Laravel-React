<?php

namespace App\Repositories;

use App\Models\Deal;
use App\Models\DealAction;

trait DealRepository {
    public function getCampaignBy($id) {
        return $this->campaigns()->where('id', $id)->first();
    }

    public function getActionsBy($dealId) {
        return $this->actions()->where('deal_id', $dealId);
    }

    public function getActionBy(int $id) {
        return $this->actions()->where('id', $id)->firstOrFail();
    }

    public function getActionByParentId(int $parentId) {
        return $this->actions()->where('parent_id', $parentId)->get();
    }

    public function updateChildrenParentIdBy(int $actionId) {
        $dealAction = $this->getActionBy($actionId);
        $dealActionChildren = $this->getActionByParentId($actionId);
        if ($dealActionChildren) {
            foreach ($dealActionChildren as $dealActionChild) {
                if ($dealAction->is_root &&
                    !$dealActionChild->is_root && (
                        $dealActionChild->type !== DealAction::TYPE_EMAIL_MESSAGE &&
                        $dealActionChild->type !== DealAction::TYPE_SMS_MESSAGE
                    )) {
                    abort(400, 'Action restricted!');
                    break;
                }
                $dealActionChild->parent_id = null;
                $dealActionChild->is_root = 1;
                if ($dealAction->parent_id) {
                    $dealActionChild->parent_id = $dealAction->parent_id;
                    $dealActionChild->is_root = $dealAction->is_root;
                }

                $dealActionChild->save();
            }
        }
    }

    public function getFirstRootAction() {
        return $this->actions()
            ->where('parent_id', null)
            ->where('is_root', 1)
            ->first();
    }
}
