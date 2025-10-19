# Delivery Platform to Loyverse POS Integration

This project provides a robust and automated solution to integrate online food delivery platforms like Careem and Talabat directly with the Loyverse Point of Sale (POS) system. It listens for new order notifications via webhooks, processes them, and creates corresponding orders in Loyverse, eliminating the need for manual data entry.

Built with the Laravel framework, it uses a queue-based system for reliability and scalability.

## Features

- **Automated Order Syncing**: Real-time order creation in Loyverse via webhooks.
- **Multi-Platform Support**: Comes with built-in support for Careem and is designed to be easily extendable for other platforms like Talabat, Deliveroo, etc.
- **Reliable Job Queues**: Ensures that every order is processed reliably, with failed jobs being automatically retried.
- **Product Mapping**: A flexible system to map product SKUs/names from the delivery platform to the correct items in Loyverse.
- **Secure Credential Management**: Safely stores API keys and tokens for third-party services.
- **Detailed Logging**: Keeps a log of all sync activities and errors for easy monitoring and debugging.

## System Architecture

1.  **Webhook Listener**: An API endpoint (`/api/webhook/{platform}`) receives incoming order data.
2.  **Job Dispatch**: A new job (e.g., `ProcessCareemOrderJob`) is dispatched to the queue.
3.  **Queue Worker**: A background worker picks up the job from the queue.
4.  **Data Transformation**: The `OrderTransformerService` converts the platform-specific order data into a standardized format.
5.  **Loyverse API**: The `LoyverseApiService` communicates with the Loyverse API to create the receipt.
6.  **Logging**: The `SyncLog` model records the outcome of the transaction.

## Installation & Setup

### Prerequisites

- PHP >= 8.2
- Composer
- Node.js & NPM
- A database (MySQL, PostgreSQL, or SQLite)
- Loyverse API Token

### Steps

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/your-username/careem-loyverse-integration.git
    cd careem-loyverse-integration
    ```

2.  **Install dependencies:**
    ```bash
    composer install
    npm install && npm run build
    ```

3.  **Environment Configuration:**
    - Copy the example environment file:
      ```bash
      cp .env.example .env
      ```
    - Generate an application key:
      ```bash
      php artisan key:generate
      ```
    - Configure your `.env` file with the following:
      - **Database credentials** (`DB_CONNECTION`, `DB_HOST`, etc.).
      - **Queue driver** (it's recommended to use `database` or `redis` for production): `QUEUE_CONNECTION=database`
      - **Loyverse API Token**: `LOYVERSE_API_TOKEN=your_loyverse_api_token`

4.  **Database Migration:**
    Run the database migrations to create the necessary tables.
    ```bash
    php artisan migrate
    ```

5.  **API Credentials Setup:**
    You need to add the API credentials for the delivery platforms you want to integrate. You can do this by seeding the database.
    - Edit `database/seeders/ApiCredentialSeeder.php` to include your platform's credentials.
    - Run the seeder:
      ```bash
      php artisan db:seed --class=ApiCredentialSeeder
      ```

## Usage

### 1. Run the Queue Worker

For the integration to process orders, the queue worker must be running. For development, you can use:

```bash
php artisan queue:work
```

For production, it is highly recommended to use a process manager like **Supervisor** to keep the queue worker running continuously. An example `queue-worker.conf` file is provided.

### 2. Configure Webhooks

In your delivery platform's admin panel (e.g., Careem), you need to set up a webhook to point to this application's endpoint. The endpoint URL will be:

`https://your-domain.com/api/webhook/{platform}`

For example, for Careem, the URL would be `https://your-domain.com/api/webhook/careem`.

The application will then start listening for and processing new orders automatically.

## Extending for a New Platform

To add a new delivery platform (e.g., "Deliveroo"):

1.  **Create a New Job**:
    Create a job similar to `app/Jobs/ProcessCareemOrderJob.php`.
    `php artisan make:job ProcessDeliverooOrderJob`

2.  **Add a Webhook Route**:
    In `routes/api.php`, add a new route for the platform:
    ```php
    Route::post('/webhook/deliveroo', function (Request $request) {
        App\Jobs\ProcessDeliverooOrderJob::dispatch($request->all(), \'deliveroo\');
        return response()->json([\'status\' => \'success\', \'message\' => \'Deliveroo order received and queued.\']);
    });
    ```

3.  **Update Services (if needed)**:
    - If the order data structure is significantly different, you may need to add a new transformation method in `app/Services/OrderTransformerService.php`.
    - Update the `ProductMappingService` if product identification logic needs to change.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).