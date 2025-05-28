<x-app title="Content Listing">
  <!-- Back to Properties Button -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1>Property Id: {{ $propertyId }}</h1>
    <a href="{{ route('properties', $accountId) }}">Back to Properties</a>
  </div>

  <h2>
    Content Listing Table
    <!-- Bulk update all button -->
    <form method="POST" action="{{ route('content.fetchUpdate', $propertyId) }}" style="display:inline;">
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

  <table>
    <thead>
      <tr>
        <th>ID</th>
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
        <th>Allow Bookings</th>
        <th>Allow Enquiries</th>
        <th>Updated At</th>

      </tr>
    </thead>
    <tbody>
      @forelse($contents as $content)
      <tr>
        <td>{{ $content->id }}</td>
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
        <td>{{ $content->allow_bookings ? 'Yes' : 'No' }}</td>
        <td>{{ $content->allow_enquiries ? 'Yes' : 'No' }}</td>
        <td>{{ $content->updated_at }}</td>
      </tr>
      @empty
      <tr>
        <td colspan="17" class="no-data">No content records found.</td>
      </tr>
      @endforelse
    </tbody>
  </table>
</x-app>
