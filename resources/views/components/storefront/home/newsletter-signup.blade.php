@once
    <style>
        .np-home-newsletter {
            padding: clamp(2.75rem, 6vw, 5.5rem) 0 clamp(2.25rem, 5vw, 4.75rem);
            background: #ffffff;
        }

        .np-home-newsletter-card {
            position: relative;
            isolation: isolate;
            display: grid;
            gap: 1.5rem;
            align-items: center;
            overflow: hidden;
            border: 1px solid rgba(233, 29, 51, .18);
            border-radius: 1.9rem;
            padding: clamp(1.6rem, 4vw, 2.75rem);
            background:
                radial-gradient(circle at 88% 12%, rgba(233, 29, 51, .28), transparent 28%),
                linear-gradient(135deg, #15345d 0%, #0d2545 58%, #071a31 100%);
            box-shadow: 0 22px 60px rgba(13, 37, 69, .17);
            color: #ffffff;
        }

        .np-home-newsletter-card::before {
            content: "";
            position: absolute;
            inset: auto -8rem -9rem auto;
            z-index: -1;
            width: 20rem;
            height: 20rem;
            border-radius: 999px;
            background: rgba(233, 29, 51, .18);
            filter: blur(.15rem);
        }

        .np-home-newsletter-copy {
            min-width: 0;
        }

        .np-home-newsletter-title {
            margin: 0;
            color: #ffffff;
            font-size: clamp(1.7rem, 3.1vw, 2.65rem);
            font-weight: 900;
            letter-spacing: -.055em;
            line-height: 1.03;
        }

        .np-home-newsletter-text {
            margin: .65rem 0 0;
            color: rgba(255, 255, 255, .78);
            font-size: clamp(.98rem, 1.35vw, 1.08rem);
            line-height: 1.6;
        }

        .np-home-newsletter-form {
            display: grid;
            gap: .85rem;
            min-width: 0;
        }

        .np-home-newsletter-control {
            display: flex;
            gap: .75rem;
            align-items: stretch;
            min-width: 0;
        }

        .np-home-newsletter-input {
            min-height: 3.55rem;
            min-width: 0;
            width: 100%;
            border: 0;
            border-radius: 999px;
            background: #ffffff;
            padding: 0 1.25rem;
            color: #111827;
            font-weight: 700;
            outline: none;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .6), 0 12px 28px rgba(2, 6, 23, .18);
        }

        .np-home-newsletter-input::placeholder {
            color: #64748b;
            font-weight: 600;
        }

        .np-home-newsletter-input:focus {
            box-shadow: inset 0 0 0 3px rgba(36, 103, 183, .28), 0 12px 28px rgba(2, 6, 23, .18);
        }

        .np-home-newsletter-button {
            min-height: 3.55rem;
            flex: 0 0 auto;
            border: 0;
            border-radius: 999px;
            background: #e91d33;
            padding: 0 1.65rem;
            color: #ffffff;
            font-weight: 900;
            white-space: nowrap;
            box-shadow: 0 14px 30px rgba(233, 29, 51, .32);
            transition: transform .18s ease, background .18s ease, box-shadow .18s ease;
        }

        .np-home-newsletter-button:hover {
            transform: translateY(-1px);
            background: #c9182b;
            box-shadow: 0 16px 36px rgba(233, 29, 51, .38);
        }

        .np-home-newsletter-message {
            margin: 0;
            border-radius: .95rem;
            padding: .75rem 1rem;
            font-size: .9rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .np-home-newsletter-message--success {
            background: rgba(16, 185, 129, .14);
            color: #d1fae5;
        }

        .np-home-newsletter-message--error {
            background: rgba(254, 226, 226, .16);
            color: #fecaca;
        }

        @media (min-width: 860px) {
            .np-home-newsletter-card {
                grid-template-columns: minmax(0, .95fr) minmax(22rem, .78fr);
            }
        }

        @media (max-width: 640px) {
            .np-home-newsletter-card {
                border-radius: 1.45rem;
            }

            .np-home-newsletter-control {
                display: grid;
            }

            .np-home-newsletter-button {
                width: 100%;
            }
        }
    </style>
@endonce

<section class="np-home-newsletter" aria-labelledby="home-newsletter-title">
    <div class="site-container">
        <div class="np-home-newsletter-card">
            <div class="np-home-newsletter-copy">
                <h2 id="home-newsletter-title" class="np-home-newsletter-title">Get team offers and kit ideas.</h2>
                <p class="np-home-newsletter-text">Useful updates only. No daily noise.</p>
            </div>

            <form method="POST" action="{{ route('newsletter.store') }}" class="np-home-newsletter-form" novalidate data-newsletter-form>
                @csrf
                <input type="hidden" name="source" value="homepage-before-footer">
                <div class="hidden" aria-hidden="true">
                    <label for="newsletter-company">Company</label>
                    <input id="newsletter-company" name="company" value="" tabindex="-1" autocomplete="off">
                </div>

                <div class="np-home-newsletter-control">
                    <label class="sr-only" for="home-newsletter-email">Email address</label>
                    <input
                        id="home-newsletter-email"
                        class="np-home-newsletter-input"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Your email address"
                        maxlength="190"
                        autocomplete="email"
                        required
                        aria-invalid="{{ $errors->newsletter->has('email') ? 'true' : 'false' }}"
                    >
                    <button class="np-home-newsletter-button" type="submit" data-newsletter-submit>Join the List</button>
                </div>

                <p
                    class="np-home-newsletter-message np-home-newsletter-message--success {{ session('newsletter_status') ? '' : 'hidden' }}"
                    role="status"
                    data-newsletter-success
                >{{ session('newsletter_status') }}</p>

                <p
                    class="np-home-newsletter-message np-home-newsletter-message--error {{ $errors->newsletter->has('email') ? '' : 'hidden' }}"
                    role="alert"
                    data-newsletter-error
                >{{ $errors->newsletter->first('email') }}</p>
            </form>
        </div>
    </div>
</section>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-newsletter-form]').forEach((form) => {
                let messageTimer = null;
                const successBox = form.querySelector('[data-newsletter-success]');
                const errorBox = form.querySelector('[data-newsletter-error]');
                const submitButton = form.querySelector('[data-newsletter-submit]');
                const emailInput = form.querySelector('input[name="email"]');

                const hideMessages = () => {
                    successBox?.classList.add('hidden');
                    errorBox?.classList.add('hidden');
                    if (successBox) successBox.textContent = '';
                    if (errorBox) errorBox.textContent = '';
                };

                const showMessage = (type, message) => {
                    window.clearTimeout(messageTimer);
                    hideMessages();

                    const box = type === 'success' ? successBox : errorBox;
                    if (! box) return;

                    box.textContent = message;
                    box.classList.remove('hidden');

                    messageTimer = window.setTimeout(() => {
                        box.classList.add('hidden');
                        box.textContent = '';
                    }, 4000);
                };

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    hideMessages();

                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.dataset.originalText = submitButton.dataset.originalText || submitButton.textContent || 'Join the List';
                        submitButton.textContent = 'Joining...';
                    }

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        const payload = await response.json().catch(() => ({}));

                        if (! response.ok) {
                            const errorMessage = payload?.errors?.email?.[0]
                                || payload?.message
                                || 'The email could not be submitted. Please try again.';
                            showMessage('error', errorMessage);
                            return;
                        }

                        showMessage('success', payload?.message || 'Thanks — you are on the list.');
                        if (emailInput) {
                            emailInput.value = '';
                            emailInput.setAttribute('aria-invalid', 'false');
                        }
                    } catch (error) {
                        showMessage('error', 'Network error. Please check your connection and try again.');
                    } finally {
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.textContent = submitButton.dataset.originalText || 'Join the List';
                        }
                    }
                });
            });
        });
    </script>
@endonce
