<div>
    <h2>Leads: period ({{$reportPayload->startDate}}-{{$reportPayload->endDate}})</h2>
</div>
<div>
    <table>
        <thead>
        <tr>
            <th>Date</th>
            <th>Status</th>
            <th>Full Name</th>
            <th>E-mail</th>
            <th>Phone</th>
            <th>Company</th>
            <th>Source</th>
        </tr>
        </thead>
        <tbody>
        @foreach($leads as $lead)
            <tr>
                <td>{{$lead->created_at->format('d/m/Y')}}</td>
                <td>{{$lead->statusInfo->name}}</td>
                <td>{{$lead->fullname}}</td>
                <td>{{$lead->email}}</td>
                <td>{{$lead->phone}}</td>
                <td>{{$lead->company['name']}}</td>
                <td>{{$lead->campaign['name']}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>