# Magento2 Asynchronous Customer Emails
## For large merchants sending emails synchronously could have a big impact on Frontend side and Customer Experience in general.
## This module allows you to send emails asynchronouslly making your frontend faster.

## Configuration
> Store > Configuration > Customer > Customer Configuration > Async emails

Add following items in env.php consumers array
* customer.forgot.pwd
* customer.new.account
* customer.cred.change

## Following emails are supported
* New account (Including confirmation if it's enabled)
* Forgot password
* Changed credentials (Email, password or both)

## Notes
Only supports AMQP connection.

## Contribution
Want to contribute to this extension? The quickest way is to open a [pull request on GitHub](https://help.github.com/articles/using-pull-requests).

## License

It is released under the [MIT License](LICENSE).