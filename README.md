# Laravel Quality Kit by SilkNetwork

**`silknetwork-us/laravel-quality-kit`** is a comprehensive collection of tools and best practices designed to enhance code quality for Laravel applications. This kit integrates several popular PHP quality assurance tools, pre-configured to work seamlessly with your Laravel project, helping you write cleaner, more robust, and maintainable code.

Below, you'll find general information about this package, followed by a detailed guide on PHPStan lessons learned within a Laravel codebase.

## 🚀 Features

This package bundles and configures the following essential tools for your Laravel project:

*   **PHPStan** ([`phpstan/phpstan`](https://phpstan.org/)): A PHP static analysis tool that focuses on finding bugs in your code without running it.
*   **Larastan** ([`calebdw/larastan`](https://github.com/larastan/larastan)): PHPStan extensions specifically for Laravel, providing a deeper understanding of Laravel's "magic" and conventions.
*   **Laravel IDE Helper** ([`barryvdh/laravel-ide-helper`](https://github.com/barryvdh/laravel-ide-helper)): Generates helper files that enable your IDE to provide accurate autocompletion, code navigation, and type hinting for Laravel's facades, models, and other components.
*   **Rector** ([`driftingly/rector-laravel`](https://github.com/driftingly/rector-laravel)): An automated refactoring tool for PHP, equipped with specific rulesets for Laravel projects to help you upgrade and modernize your codebase safely.
*   **Laravel Pint** ([`laravel/pint`](https://github.com/laravel/pint)): An opinionated PHP code style fixer built on top of PHP-CS-Fixer, designed for minimal configuration and ease of use.
*   **Peck** ([`peckphp/peck`](https://github.com/PeckPHP/peck)): A command-line tool to check for spelling errors in your PHP code comments, strings, and documentation blocks.

## ✅ Requirements

*   PHP: `^8.2` (as specified in [`composer.json`](composer.json:7))

## 🛠️ Installation

For detailed installation instructions, please refer to the [INSTALLATION.md](INSTALLATION.md:1) guide.

A quick overview:

1.  Require the package via Composer:
    ```bash
    composer require silknetwork-us/laravel-quality-kit:dev-main --dev
    ```

2.  Run the bootstrap command to publish configurations and generate essential helper files:
    ```bash
    php artisan silknetwork:bootstrap
    ```

## ⚙️ Usage

This kit provides several convenient Composer scripts for executing the integrated tools:

*   **Run PHPStan for static analysis:**
    ```bash
    vendor/bin/phpstan analyse
    ```

*   **Run Rector for code refactoring (it's recommended to run with `--dry-run` first):**
    ```bash
    vendor/bin/rector process --dry-run
    # To apply changes:
    # vendor/bin/rector process
    ```
    *(The `composer rector` script directly runs `vendor/bin/rector`. Adjust your `composer.json` or use the direct command for more control over options like `--dry-run`.)*

*   **Check code formatting with Pint (test mode):**
    ```bash
    vendor/bin/pint --config=config/pint.json --test
    ```

*   **Apply code formatting with Pint:**
    ```bash
    vendor/bin/pint --config=config/pint.json
    ```

*   **Run Peck for spell checking:**
    ```bash
    vendor/bin/peck
    ```

## 🔧 Configuration

The `silknetwork:bootstrap` command publishes the default configuration files for PHPStan (`phpstan.neon`), Rector ([`rector.php`](rector.php:1)), Pint ([`config/pint.json`](config/pint.json:1)), and Peck ([`config/peck.json`](config/peck.json:1)) into your project. You can (and should) customize these files to better suit your project's specific coding standards and requirements.

Detailed information on published files and further customization options can be found in the [INSTALLATION.md](INSTALLATION.md:1) guide.

## 🤝 Contributing

Contributions are highly welcome! If you have suggestions, bug reports, or want to contribute to the code, please feel free to submit a pull request or create an issue on the GitHub repository.

---
# ✅ PHPStan Lessons Learned – Laravel Codebase

This guide outlines common static analysis issues we’ve encountered using **PHPStan** with **Laravel**, and how to fix them. Following these practices will help improve type safety, prevent bugs, and reduce noise in our baseline.

---

## 🧠 Why This Guide Exists

We use PHPStan to:

* Catch bugs early through static analysis
* Keep our code maintainable and predictable
* Minimize fragile dynamic behavior (especially with Eloquent)
* Ensure team-wide consistency and confidence in code correctness

---

## 🔤 Type Declarations

### ✅ Array Types in Properties and Parameters

**Issue**:

> *"No value type specified in iterable type array"*

**Fix**: Use PHPDoc annotations to declare array key/value types:

```php
/** @var array<string, mixed>|null */
public ?array $customData = null;
```

### ⛔ Do NOT Use Generics in Native PHP Syntax

```php
// ❌ Invalid - Causes syntax error
public ?array<string, mixed> $customData = null;

// ✅ Correct - Use PHPDoc above native type
/** @var array<string, mixed>|null */
public ?array $customData = null;
```

---

### ✅ Return Types for Methods

**Issue**:

> *"Method has no return type specified"*

**Fix**: Always specify method return types:

```php
// ❌ Before
public function handle()
{
    // ...
}

// ✅ After
public function handle(): int
{
    return Command::SUCCESS;
}
```

> 📝 For Laravel Artisan commands, use `Command::SUCCESS`, `Command::FAILURE`, or `Command::INVALID`.

---

## 🔍 Null Safety & Type Guards

### `str_contains`, `str_starts_with`, etc.

**Issue**:

> *"str\_contains expects string, string|false given"*

**Fix**:

```php
// ✅ Check type first
if (is_string($robots) && str_contains($robots, 'Sitemap:')) {
    // ...
}
```

---

### Accessing Properties on Nullable Models

**Issue**:

> *"Access to an undefined property Illuminate\Database\Eloquent\Model::\$email"*

**Fix Option 1: Null check**

```php
$user = User::find($id);
if ($user !== null) {
    Mail::to($user->email)->send(...);
}
```

**Fix Option 2: `findOrFail()`**

```php
$user = User::findOrFail($id);
Mail::to($user->email)->send(...);
```

---

## ⚠️ Using `@var` vs. `instanceof` or `findOrFail`

### ❌ Don't Do This:

```php
/** @var \App\Models\User $user */
$user = User::find($id);

if (! $user instanceof User) {
    // contradiction!
}
```

* The `@var` annotation claims `$user` is a `User`
* The `instanceof` check says you're not sure

These two conflict, and confuse static analysis tools.

---

### ✅ Instead, Use One of These:

**Option 1: `instanceof`**

```php
$user = User::find($id);
if (! $user instanceof User) {
    throw new NotFoundException();
}
// $user is a User here
```

**Option 2: `findOrFail()`**

```php
$user = User::findOrFail($id);
// No need for type checks — $user is a User
```

**Option 3: `assert()`**

```php
$user = User::find($id);
assert($user instanceof User);
// PHPStan now knows $user is a User
```

---

## 🏗️ Eloquent: Properties & Methods

### Accessing Dynamic Properties

**Issue**:

> *"Access to undefined property Illuminate\Database\Eloquent\Model::\$foo"*

**Fix Option 1: Inline PHPDoc**

```php
/** @var \App\Models\User $user */
$user = User::find($id);
echo $user->email;
```

**Fix Option 2: Add `@property` annotations to your model**

```php
/**
 * @property string $email
 * @property \Illuminate\Support\Carbon $created_at
 */
class User extends Model
```

---

### Calling Dynamic Methods (e.g., relationships)

**Issue**:

> *"Call to an undefined method Model::subscriptions()"*

**Fix**: PHPStan understands dynamic methods if the variable is explicitly typed.

```php
/** @var \App\Models\User $user */
$user = User::find($id);
$user->subscriptions(); // Valid now
```

---

## 📦 Collection Types

**Issue**:

> *"Generic class Illuminate\Support\Collection does not specify its types: TKey, TValue"*

**Fix**: Use PHPDoc to define key and value types:

```php
/**
 * @param \Illuminate\Support\Collection<int, \App\Models\User> $users
 */
public function processUsers(Collection $users): void
{
    // ...
}
```

> Laravel collections are generic — PHPStan needs to know what types they contain.

---

## 📦 Strong Typing With DTOs

Repeated use of `array<string, mixed>` or `array{...}` hints that you should use **Data Transfer Objects** (DTOs).

**Recommendation:**
Use [`spatie/laravel-data`](https://github.com/spatie/laravel-data) to define structured, typed data.

```php
use Spatie\LaravelData\Data;

class CreateUserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
```

Then in a service:

```php
public function createUser(CreateUserData $data): User
{
    return User::create([
        'name' => $data->name,
        'email' => $data->email,
        'password' => Hash::make($data->password),
    ]);
}
```

✅ Improves IDE completion, PHPStan understanding, and removes need for array shape annotations.

---

## 📌 Best Practices Summary

| ✅ Do This                                    | ❌ Avoid This                             |
| -------------------------------------------- | ---------------------------------------- |
| Add return types to all methods              | Omitting return types                    |
| Use PHPDoc for arrays & collections          | Using raw `array` with no shape/type     |
| Use `findOrFail()` if null is not acceptable | Assuming `find()` never returns null     |
| Annotate models with `@property`             | Letting PHPStan guess model structure    |
| Use DTOs for structured input                | Passing raw arrays everywhere            |
| Use `is_string()` instead of `!== false`     | Weak comparisons on return types         |
| Keep baseline lean                           | Suppressing fixable issues               |
| Use `assert()` or `instanceof` as needed     | Combining `@var` and runtime type checks |

---

## 🧾 Common PHPStan Error Identifiers

| Identifier                  | Meaning                                   |
| --------------------------- | ----------------------------------------- |
| `missingType.return`        | Method has no return type                 |
| `missingType.iterableValue` | Array has no value type specified         |
| `missingType.generics`      | Generic class missing key/value types     |
| `property.notFound`         | Access to undefined property              |
| `method.notFound`           | Call to undefined method                  |
| `argument.type`             | Argument type mismatch                    |
| `return.type`               | Return type mismatch                      |
| `nullsafe.neverNull`        | Nullsafe call used on non-nullable object |

---

## ▶️ Running PHPStan

```bash
./vendor/bin/phpstan analyse
```

### To Generate a Baseline (only if needed):

```bash
./vendor/bin/phpstan analyse --generate-baseline
```

> ⚠️ Only generate a new baseline if you're suppressing **non-fixable** issues like dynamic Eloquent properties.

---

## 🏁 Final Note

Fixing PHPStan issues is not just about silencing warnings — it helps enforce correctness across your entire project. Every return type, every docblock, and every null check is a step toward code that’s robust, readable, and safe.