<x-app title="Property Content Listing">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1>Property ID: {{ $propertyId }}</h1>
    <a href="{{ route('properties', $accountId) }}">← Back to Properties</a>
  </div>

  <h2>
    Property Content

    <form action="{{ route('property-content.fetchUpdate', $propertyId) }}" method="POST">
    @csrf
    <button type="submit">Fetch & Update Property Content</button>
</form>
  </h2>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  @forelse ($contents as $content)
    <div style="margin-top: 40px; padding: 20px; border: 1px solid #ccc; border-radius: 10px;">
      <h3>{{ $content->property_name ?? 'Unnamed Property' }}</h3>

      <p><strong>Description:</strong> {{ $content->description }}</p>
      <p><strong>Subcaption:</strong> {{ $content->subcaption }}</p>
      <p><strong>Accommodation Summary:</strong> {{ $content->accommodations_summary }}</p>
      <p><strong>Location:</strong> {{ $content->address_line }}, {{ $content->city }}, {{ $content->country }} {{ $content->post_code }}</p>
      <p><strong>Bedrooms:</strong> {{ $content->bedroom_count }} | <strong>Bathrooms:</strong> {{ $content->bathroom_count }}</p>
      <p><strong>Latitude/Longitude:</strong> {{ $content->latitude }} / {{ $content->longitude }}</p>
      <p><strong>Area:</strong> {{ $content->area }} {{ $content->area_unit }}</p>
      <p><strong>Changeover:</strong> {{ $content->changeover }}</p>
      <p><strong>Registration Number:</strong> {{ $content->registration_number }}</p>
      <p><strong>EPC Rating:</strong> {{ $content->epcRating }}</p>
      <p><strong>Amenities:</strong> {{ is_array($content->amenities) ? implode(', ', $content->amenities) : $content->amenities }}</p>

      {{-- Images --}}
      <h4>Images:</h4>
      <div style="display: flex; flex-wrap: wrap; gap: 10px;">
        @foreach($content->images as $img)
          <div style="width: 150px;">
            <!-- <img src="{{ $img->image_url }}" alt="Image" style="width: 100%; border-radius: 6px;"> -->
             <img src="{{ $img->url }}" alt="Image" style="width: 100%; border-radius: 6px;">
            <p style="text-align: center; margin-top: 5px;">{{ $img->caption }}</p>
          </div>
        @endforeach
      </div>

      {{-- Policies --}}

      {{-- Rooms & Beds --}}
      <h4 style="margin-top: 20px;">Rooms & Beds:</h4>
      @foreach($content->rooms as $room)
        <div style="margin-top: 10px;">
          <strong>Room:</strong> {{ $room->name }}
          <ul>
            @foreach($room->beds as $bed)
              <li>{{ $bed->quantity }} × {{ $bed->bed_type }}</li>
            @endforeach
          </ul>
        </div>
      @endforeach
    </div>
  @empty
    <p>No content available for this property.</p>
  @endforelse
</x-app>
