<div>
    <table style="width: 100%;">
        <thead>
        <tr> 
            <th style="width: 20%;">Name</th>
            <th style="width: 30%;">E-mail</th>
            <th>Phone</th>
            <th style="text-align: right;">Campaigns</th>
            <th style="text-align: right;">Leads</th>
            <th style="text-align: right;">Response Time</th>
        </tr>
        </thead>
        <tbody>
        @foreach($companies as $lead)
            <tr>
                <td>{{$lead->name}}</td>
                <td>{{$lead->email}}</td>
                <td>{{$lead->phone}}</td> 
                <td style="text-align: right;">{{$lead->deals_count}}</td> 
                <td style="text-align: right;">{{$lead->leads_count}}</td> 
                <td style="text-align: right;">{{$lead->avg_lead_response}}</td> 
            </tr>
        @endforeach
        </tbody>
    </table>
</div>