<div>
	<p>
		{!! $body !!}
	</p>
	<img src="{{action([\App\Http\Controllers\Api\LeadReplyController::class , 'onMailReply'], [ 'lead' => $leadId, 'dealAction' => $dealActionId ])}}" width="1" height="1">
</div>
