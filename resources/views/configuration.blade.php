<x-app title="Configurations">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1>Account Id: {{ $account->accountId }}</h1>
    <a href="{{ route('accounts.index') }}">Back to Accounts</a>
  </div>
  <h2>
    Configurations Table
    <a href="{{ route('fetch.configuration', $account->id) }}">Fetch & Update Configurations</a>
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
        <th>Account ID</th>
        <th>Card Code</th>
        <th>Card Type</th>
        <th>Cancellation Policy</th>
        <th>Merchant Name</th>
        <th>Minimum Age</th>
        <th>Rental Agreement URL</th>
        <th>Config Content URL</th>
        <th>Last Updated</th>
        <th>Created At</th>
        <th>Updated At</th>
      </tr>
    </thead>
    <tbody>
      @forelse($configurations as $config)
        <tr>
          <td>{{ $config->id }}</td>
          <td>{{ $config->property_id }}</td>
          <td>{{ $config->account_id }}</td>
          <td>{{ $config->card_code }}</td>
          <td>{{ $config->card_type }}</td>
          <td>{{ $config->cancellation_policy }}</td>
          <td>{{ $config->merchant_name }}</td>
          <td>{{ $config->minimum_age }}</td>
          <td>
            @if($config->rental_agreement_url)
              <a href="{{ $config->rental_agreement_url }}" target="_blank">Link</a>
            @endif
          </td>
          <td>
            @if($config->configurationContentUrl)
              <a href="{{ $config->configurationContentUrl }}" target="_blank">Link</a>
            @endif
          </td>
          <td>{{ $config->last_updated }}</td>
          <td>{{ $config->created_at }}</td>
          <td>{{ $config->updated_at }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="13" class="no-data">No configurations found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</x-app>
