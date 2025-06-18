<x-app title="Accounts">
  <h2>
    Accounts Table
    <a href="{{ route('fetch.accounts') }}">Fetch & Update Accounts</a>
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
        <th>Company Name</th>
        <th>Content Index URL</th>
        <th>Configuration Index URL</th>
        <th>Prices Index URL</th>
        <th>Availability Index URL</th>
        <th>Created At</th>
        <th>Updated At</th>
        <th>Configurations</th>
        <th>Properties</th>
        <th>Availability</th>
      </tr>
    </thead>
    <tbody>
      @forelse($accounts as $account)
      <tr>
      <td>{{ $account->id }}</td>
      <td>{{ $account->accountId }}</td>
      <td>{{ $account->companyName }}</td>
      <td><a href="{{ $account->contentIndexUrl }}" target="_blank">Link</a></td>
      <td><a href="{{ $account->configurationIndexUrl }}" target="_blank">Link</a></td>
      <td><a href="{{ $account->pricesIndexUrl }}" target="_blank">Link</a></td>
      <td><a href="{{ $account->availabilityIndexUrl }}" target="_blank">Link</a></td>
      <td>{{ $account->created_at }}</td>
      <td>{{ $account->updated_at }}</td>
      <td>
        <a href="{{ route('configurations.index', $account->id) }}">View</a>
      </td>
      <td>
        <a href="{{ route('properties', $account->accountId) }}">View</a>
      </td>
      <td>
        <a href="{{ route('availability.index', $account->accountId) }}">View</a>
      </td>
      </tr>
    @empty
      <tr>
      <td colspan="10" class="no-data">No accounts found.</td>
      </tr>
    @endforelse
    </tbody>
  </table>
</x-app>