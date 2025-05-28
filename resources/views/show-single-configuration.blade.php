<h2>
  Property Configuration Listing
  <button id="fetchConfigBtn">Fetch & Update Property Configuration</button>
</h2>

<table border="1" cellpadding="8" cellspacing="0">
  <thead>
    <tr>
      <th>ID</th>
      <th>Property ID</th>
      <th>Property URL</th>
      <th>Customer Property ID</th>
      <th>Card Payment Forms</th>
      <th>Cancellation Policy</th>
      <th>Check-in Time</th>
      <th>Check-out Time</th>
      <th>Children Allowed</th>
      <th>Max Adults</th>
      <th>Max Guests</th>
      <th>Max Children</th>
      <th>Merchant Name</th>
      <th>Pets Allowed</th>
      <th>Smoking Allowed</th>
      <th>Allow Bookings</th>
      <th>Allow Enquiries</th>
      <th>Created At</th>
      <th>Updated At</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>{{ $config->id }}</td>
      <td>{{ $config->property_id }}</td>
      <td><a href="{{ $config->property_url }}" target="_blank">Link</a></td>
      <td>{{ $config->customer_property_id }}</td>
      <td>
        @foreach($config->card_payment_forms as $card)
          <div>{{ $card['cardCode'] }} - {{ $card['cardType'] }}</div>
        @endforeach
      </td>
      <td>{{ $config->cancellation_policy }}</td>
      <td>{{ $config->check_in_time }}</td>
      <td>{{ $config->check_out_time }}</td>
      <td>{{ $config->children_allowed ? 'Yes' : 'No' }}</td>
      <td>{{ $config->max_adults }}</td>
      <td>{{ $config->max_guests }}</td>
      <td>{{ $config->max_children }}</td>
      <td>{{ $config->merchant_name }}</td>
      <td>{{ $config->pets_allowed ? 'Yes' : 'No' }}</td>
      <td>{{ $config->smoking_allowed ? 'Yes' : 'No' }}</td>
      <td>{{ $config->allow_bookings ? 'Yes' : 'No' }}</td>
      <td>{{ $config->allow_enquiries ? 'Yes' : 'No' }}</td>
      <td>{{ $config->created_at }}</td>
      <td id="updatedAt">{{ $config->updated_at }}</td>
      <td><button onclick="toggleFetch()">Show JSON</button></td>
    </tr>
    <tr>
      <td colspan="20">
        <div id="fetch-data" class="fetched-data" style="display: none;">
          {{ json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
        </div>
      </td>
    </tr>
  </tbody>
</table>

<script>
  function toggleFetch() {
    const el = document.getElementById('fetch-data');
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
  }

  document.getElementById('fetchConfigBtn').addEventListener('click', function () {
    fetch("{{ route('fetch.update.single') }}")
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        document.getElementById('updatedAt').innerText = data.updated_at;
      })
      .catch(err => {
        console.error("Error:", err);
        alert("Failed to update.");
      });
  });
</script>
