<p>Hello,</p>

<p>Here is the list of standard-urgency insurance verifications due for {{ $payload['report_date'] }}.</p>

@if (empty($payload['items']))
    <p>No standard-urgency verifications are scheduled for tomorrow.</p>
@else
    <table cellpadding="6" cellspacing="0" border="1">
        <thead>
            <tr>
                <th>Clinic</th>
                <th>Patient</th>
                <th>Email</th>
                <th>Appointment</th>
                <th>Provider</th>
                <th>Slot</th>
                <th>Insurance</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payload['items'] as $item)
                <tr>
                    <td>{{ $item['clinic'] }}</td>
                    <td>{{ $item['patient'] }}</td>
                    <td>{{ $item['email'] }}</td>
                    <td>{{ $item['appointment_type'] }}</td>
                    <td>{{ $item['provider'] }}</td>
                    <td>{{ $item['slot_local'] }} ({{ $item['timezone'] }})</td>
                    <td>{{ $item['insurance_provider'] ?? 'N/A' }} ({{ $item['member_id'] ?? 'N/A' }})</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<p>Review these items in the Insurance Verification Queue.</p>
