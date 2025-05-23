
<x-app title="Availabilities">
  <h2>
    Availability Table
    <a href="{{ route('fetch.availability') }}">Fetch & Update Availability</a>
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
        <th>Account ID</th>
        <th>Property ID</th>
        <th>Listing URL</th>
        <th>Last Updated</th>
        <th>Created At</th>
        <th>Updated At</th>
      </tr>
    </thead>
    <tbody>
      @forelse($availabilities as $availability)
        <tr>
          <td>{{ $availability->id }}</td>
          <td>{{ $availability->account_id }}</td>
          <td>{{ $availability->property_id }}</td>
          <td>
            @if($availability->listing_url)
              <a href="{{ $availability->listing_url }}" target="_blank">Link</a>
            @else
              N/A
            @endif
          </td>
          <td>{{ $availability->last_updated ?? 'N/A' }}</td>
          <td>{{ $availability->created_at }}</td>
          <td>{{ $availability->updated_at }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="no-data">No availability records found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</x-app>
