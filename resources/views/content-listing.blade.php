<x-app title="Content Listing">
  <h2>
    Content Listing Table

    <!-- Bulk update all button as form POST -->
    <form method="POST" action="{{ route('content.fetchUpdate') }}" style="display:inline;">
  @csrf
  <button type="submit" style="margin-left: 20px;">Fetch & Update All Content</button>
</form>
  </h2>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
  @endif

  <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse: collapse;">
    <thead>
      <tr>
        <th>Property ID</th>
        <th>Customer Property ID</th>
        <th>URL</th>
        <th>Merchant</th>
        <th>Adults</th>
        <th>Children</th>
        <th>Guests</th>
        <th>Check-In</th>
        <th>Check-Out</th>
        <th>Children Allowed</th>
        <th>Pets Allowed</th>
        <th>Smoking Allowed</th>
        <th>Updated At</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      @forelse($contents as $content)
        <tr>
          <td>{{ $content->property_id }}</td>
          <td>{{ $content->customer_property_id }}</td>
          <td>
            @if($content->property_url)
              <a href="{{ $content->property_url }}" target="_blank">Link</a>
            @else
              N/A
            @endif
          </td>
          <td>{{ $content->merchant_name }}</td>
          <td>{{ $content->maximum_adults }}</td>
          <td>{{ $content->maximum_children }}</td>
          <td>{{ $content->maximum_guests }}</td>
          <td>{{ $content->check_in_time }}</td>
          <td>{{ $content->check_out_time }}</td>
          <td>{{ $content->children_allowed ? 'Yes' : 'No' }}</td>
          <td>{{ $content->pets_allowed ? 'Yes' : 'No' }}</td>
          <td>{{ $content->smoking_allowed ? 'Yes' : 'No' }}</td>
          <td id="updated-{{ $content->id }}">{{ $content->updated_at }}</td>
          <td>
            <button onclick="updateTime({{ $content->id }})">Update Time</button>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="14" class="no-data">No content records found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <script>
    function updateTime(id) {
      fetch(`/content-listing/update/${id}`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json'
        }
      })
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        document.getElementById(`updated-${id}`).innerText = data.updated_at;
      })
      .catch(err => {
        console.error(err);
        alert("Error updating content.");
      });
    }
  </script>
</x-app>
