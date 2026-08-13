<?php

namespace App\Policies;

use App\Models\Guide;
use App\Models\User;

class GuidePolicy
{
    /**
     * Editing rights: the guide's own author, or anyone with approver-level
     * standing (approvers and admins). A route-level "contributor" gate
     * gets someone into the builder; this resource-level check is what
     * stops a contributor from editing a colleague's guide.
     */
    public function update(User $user, Guide $guide): bool
    {
        return $user->isApprover() || $guide->created_by === $user->id;
    }

    /**
     * Publishing/sending back is an approver-only action, deliberately
     * excluding the guide's own author -- self-approval would defeat the
     * point of a review queue.
     */
    public function publish(User $user, Guide $guide): bool
    {
        return $user->isApprover();
    }
}
