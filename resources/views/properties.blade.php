<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="UTF-8">
  <title>Properties Table</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
    }

    h2 {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    h2 a.button-link {
      padding: 8px 15px;
      background-color: #007BFF;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      text-decoration: none;
    }

    h2 a.button-link:hover {
      background-color: #0056b3;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    th, td {
      border: 1px solid #ddd;
      padding: 10px;
      font-size: 14px;
      text-align: left;
    }

    th {
      background-color: #f2f2f2;
    }

    a {
      color: #007BFF;
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }

    button.show-json {
      padding: 6px 10px;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 13px;
    }

    button.show-json:hover {
      background-color: #218838;
    }

    .fetched-data {
      display: none;
      background-color: #f9f9f9;
      border: 1px solid #ddd;
      padding: 10px;
      font-family: monospace;
      white-space: pre-wrap;
      font-size: 13px;
      margin-top: 5px;
    }

    .no-data {
      text-align: center;
      font-style: italic;
      color: #888;
    }

    .alert {
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
    }
  </style>
</head>
<body>

  <h2>
    Properties Table
    <a href="{{ route('fetch.properties') }}" class="button-link">Fetch & Update Properties</a>
  </h2>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
  @endif

  @if(count($properties) > 0)
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Account ID</th>
          <th>Property ID</th>
          <th>Active</th>
          <th>Last Updated</th>
          <th>Listing URL</th>
          <th>Created At</th>
          <th>Updated At</th>
          <th>Action</th>
          <th>Config</th>
        </tr>
      </thead>
      <tbody>
        @foreach($properties as $index => $property)
          <tr>
            <td>{{ $property->id }}</td>
            <td>{{ $property->account_id }}</td>
            <td>{{ $property->propertyId }}</td>
            <td>{{ $property->active ? 'Yes' : 'No' }}</td>
            <td>{{ $property->lastUpdated }}</td>
            <td><a href="{{ $property->listingUrl }}" target="_blank">Link</a></td>
            <td>{{ $property->created_at }}</td>
            <td>{{ $property->updated_at }}</td>
            <td>
              <button class="show-json" onclick="toggleFetch({{ $index }})">Show JSON</button>
            </td>
            <td>
                <a href="{{ route('single.property.listing', $property->id) }}">Link</a>
            </td>
          </tr>
          <tr>
            <td colspan="9">
              <div id="fetch-data-{{ $index }}" class="fetched-data">
                {{ json_encode($property, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <p class="no-data">No properties found for this account.</p>
  @endif

  <script>
    function toggleFetch(index) {
      const el = document.getElementById(`fetch-data-${index}`);
      el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
  </script>

</body>
</html>
