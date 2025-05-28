<x-app title="Single Property Configurations">
 <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1>Property Id: {{ $property->id }}</h1>
    <a href="{{ route('properties', $property->account_id) }}">Back to Properties</a>
</div>
  <h2>
    Single Property Listing
    <a href="{{ route('single.configuration.fetch', $property->id) }}">Fetch & Update Single Property Configuration</a>
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
        <th>Property URL</th>
        <th>Customer Property ID</th>
        <th>Card Codes</th>
        <th>Card Types</th>
        <th>Cancellation Policy</th>
        <th>Check-in</th>
        <th>Check-out</th>
        <th>Children</th>
        <th>Adults</th>
        <th>Guests</th>
        <th>Children Max</th>
        <th>Merchant</th>
        <th>Pets</th>
        <th>Smoking</th>
        <th>Bookings</th>
        <th>Enquiries</th>
        <th>Created At</th>
        <th>Updated At</th>
        <th>JSON</th>
        <th>Price</th>
      </tr>
    </thead>
    <tbody>
      @forelse($configurations as $configuration)
        <tr>
          <td>{{ $configuration->id }}</td>
          <td>{{ $configuration->property_id }}</td>
          <td>
            @if($configuration->property_url)
              <a href="{{ $configuration->property_url }}" target="_blank">Link</a>
            @else
              N/A
            @endif
          </td>
          <td>{{ $configuration->customer_property_id }}</td>
          <td>
           
          </td>
          <td>
           
          </td>
          <td>{{ $configuration->cancellation_policy }}</td>
          <td>{{ $configuration->check_in_time }}</td>
          <td>{{ $configuration->check_out_time }}</td>
          <td>{{ $configuration->children_allowed ? 'Yes' : 'No' }}</td>
          <td>{{ $configuration->max_adults }}</td>
          <td>{{ $configuration->max_guests }}</td>
          <td>{{ $configuration->max_children }}</td>
          <td>{{ $configuration->merchant_name }}</td>
          <td>{{ $configuration->pets_allowed ? 'Yes' : 'No' }}</td>
          <td>{{ $configuration->smoking_allowed ? 'Yes' : 'No' }}</td>
          <td>{{ $configuration->allow_bookings ? 'Yes' : 'No' }}</td>
          <td>{{ $configuration->allow_enquiries ? 'Yes' : 'No' }}</td>
          <td>{{ $configuration->created_at }}</td>
          <td>{{ $configuration->updated_at }}</td>
          <td>
            <button onclick="toggleJSON({{ $configuration->id }})">Show JSON</button>
          </td>
          <td>
            <a href="{{ route('price.listing', $configuration->property_id) }}">View</a>
          </td>
        </tr>
        <tr id="jsonRow-{{ $configuration->id }}" style="display: none;">
          <td colspan="22">
            <div style="background: #f8f8f8; padding: 10px;">
              <pre>{{ json_encode($configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="22" class="no-data">No configuration records found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <script>
    function toggleJSON(id) {
      const row = document.getElementById(`jsonRow-${id}`);
      row.style.display = row.style.display === "none" ? "table-row" : "none";
    }
  </script>
</x-app>


