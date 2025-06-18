<x-app title="Availability Records">
  <h2>
    Property Availability Records
    <a href="{{ route('availability-records.fetch') }}">Fetch & Update Records</a>
  </h2>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
  @endif

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Property ID</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Availability</th>
        <th>Change Over</th>
        <th>Max Stay</th>
        <th>Min Stay</th>
        <th>Min Prior Notify</th>
        <th>Created At</th>
        <th>Updated At</th>
      </tr>
    </thead>
    <tbody>
      @forelse($records as $record)
        <tr>
          <td>{{ $record->id }}</td>
          <td>{{ $record->property_id }}</td>
          <td>{{ $record->start_date }}</td>
          <td>{{ $record->end_date }}</td>
          <td>{{ $record->availability }}</td>
          <td>{{ $record->change_over }}</td>
          <td>{{ $record->max_stay }}</td>
          <td>{{ $record->min_stay }}</td>
          <td>{{ $record->min_prior_notify }}</td>
          <td>{{ $record->created_at }}</td>
          <td>{{ $record->updated_at }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="11" class="no-data">No availability records found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</x-app>
