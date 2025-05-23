<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="UTF-8">
  <title>Prices Table</title>
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

    h2 button {
      padding: 8px 15px;
      background-color: #007BFF;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
    }

    h2 button:hover {
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
    Prices Table
    <button id="fetchPricesBtn">Fetch & Update Prices</button>
  </h2>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
  @endif

  @if(count($prices) > 0)
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
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($prices as $index => $price)
          <tr>
            <td>{{ $price->id }}</td>
            <td>{{ $price->account_id }}</td>
            <td>{{ $price->property_id }}</td>
            <td><a href="{{ $price->listing_url }}" target="_blank">Link</a></td>
            <td>{{ $price->last_updated }}</td>
            <td>{{ $price->created_at }}</td>
            <td>{{ $price->updated_at }}</td>
            <td>
              <button class="show-json" onclick="toggleFetch({{ $index }})">Show JSON</button>
            </td>
          </tr>
          <tr>
            <td colspan="8">
              <div id="fetch-data-{{ $index }}" class="fetched-data">
                {{ json_encode($price, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <p class="no-data">No prices found for this account.</p>
  @endif

  <form id="fetchPricesForm" method="POST" action="{{ route('prices.fetch') }}" style="display:none;">
    @csrf
  </form>

  <script>
    function toggleFetch(index) {
      const el = document.getElementById(`fetch-data-${index}`);
      el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }

    document.getElementById('fetchPricesBtn').addEventListener('click', function () {
      document.getElementById('fetchPricesForm').submit();
    });

    setInterval(() => {
      fetch("{{ route('prices.fetch') }}", {
        method: 'GET',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
      })
      .then(res => {
        if (res.ok) {
          return res.text(); 
        } else {
          throw new Error('Fetch failed.');
        }
      })
      .then(() => location.reload())
      .catch(err => {
        console.error("Auto-refresh error:", err);
      });
    }, 15000);
  </script>

</body>
</html>






