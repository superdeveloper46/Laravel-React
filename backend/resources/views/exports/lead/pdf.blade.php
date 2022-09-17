<style type="text/css">
    .lead {
    }
    .lead label {
        font-weight: bold;
    }
    .lead-notes label {
        font-weight: bold;
    }

    .lead-notes .message {
        border-bottom: 1px solid gray;
    }

    .lead h2 {
        color: #0d71bb;
    }
    .lead-notes h2 {
        color: #00b5ad;
    }
</style>
<div class="lead">
    <h2>Lead Info, <strong>Status: {{ $lead->statusInfo->name }}</strong></h2>
    <h3 class="status_{{ $lead->statusInfo->type  }}">Current Status: {{ $lead->statusInfo->name }}</h3>
    <p class="lead-info">
        <p><label>Full name: </label> {{ $lead->fullname }}</p>
        <p><label>Phone:</label> {{ $lead->phone }}</p>
        <p><label>Email:</label> {{ $lead->email }}</p>
    </p>
    <p class="lead-metadata">
        <label>Metadata: </label> {{ $lead->metadata  }}
    </p>
</div>
<div class="lead-notes">
    <h2>Lead notes</h2>
    @foreach($lead->leadNotes as $note)
        <div class="message">
            <div>
                <label>Date:</label> {{ $note->created_at->format('d/m/Y') }}
            </div>
            <div>
                <label>Author:</label> {{ $note->agent->name  }}
            </div>
            <p>
                <label>Message: </label>{{ $note->message }}
            </p>
        </div>
    @endforeach
</div>