@props(['steps' => []])

<section id="process" class="process-section" aria-labelledby="process-heading">
    <div class="container">
        <div class="process-intro">
            <span class="small-red">How it works</span>
            <h2 id="process-heading">Simple Ordering Process</h2>
            <p>A clear process from product selection to delivery.</p>
        </div>

        <div class="process" aria-label="Ordering process steps">
            @foreach($steps as $step)
                <article class="process-step">
                    <span class="process-number">{{ $loop->iteration }}</span>

                    <div class="process-card">
                        <div class="process-illustration" aria-hidden="true">
                            @switch($loop->iteration)
                                @case(1)
                                    <svg viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M31 25 42 18h12l11 7 14 7-8 17-9-4v32H34V45l-9 4-8-17 14-7Z" fill="#ffffff" stroke="#0d2545" stroke-width="4" stroke-linejoin="round"/>
                                        <path d="M40 19c1.7 5.2 4.4 7.8 8 7.8s6.3-2.6 8-7.8" stroke="#e91d33" stroke-width="3" stroke-linecap="round"/>
                                        <path d="M45 41h8v25" stroke="#0d2545" stroke-width="4" stroke-linecap="round"/>
                                        <path d="M39 66h21" stroke="#0d2545" stroke-width="4" stroke-linecap="round"/>
                                        <path d="M28 30 20 34M68 30l8 4" stroke="#e91d33" stroke-width="3" stroke-linecap="round"/>
                                    </svg>
                                    @break

                                @case(2)
                                    <svg viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="20" y="16" width="48" height="60" rx="4" fill="#ffffff" stroke="#0d2545" stroke-width="4"/>
                                        <path d="M30 32h12M30 47h27M30 60h20" stroke="#0d2545" stroke-width="4" stroke-linecap="round"/>
                                        <path d="M50 31h9M50 46h9" stroke="#e91d33" stroke-width="3" stroke-linecap="round"/>
                                        <circle cx="66" cy="66" r="17" fill="#ffffff" stroke="#0d2545" stroke-width="4"/>
                                        <circle cx="66" cy="61" r="5" stroke="#0d2545" stroke-width="3"/>
                                        <path d="M55 77c2.4-7 6-10 11-10s8.6 3 11 10" stroke="#0d2545" stroke-width="3" stroke-linecap="round"/>
                                    </svg>
                                    @break

                                @case(3)
                                    <svg viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="16" y="18" width="64" height="48" rx="5" fill="#ffffff" stroke="#0d2545" stroke-width="4"/>
                                        <path d="M42 27 49 23h8l7 4 7 4-4 9-5-2v19H44V38l-5 2-4-9 7-4Z" fill="#ffffff" stroke="#0d2545" stroke-width="3" stroke-linejoin="round"/>
                                        <path d="M49 24c1.2 3 3 4.5 5 4.5s3.8-1.5 5-4.5" stroke="#e91d33" stroke-width="2.5" stroke-linecap="round"/>
                                        <path d="M52 37h5v14" stroke="#e91d33" stroke-width="2.7" stroke-linecap="round"/>
                                        <path d="M67 29h6M67 38h6M67 47h6" stroke="#0d2545" stroke-width="3" stroke-linecap="round"/>
                                        <path d="M38 74h20M48 66v8" stroke="#0d2545" stroke-width="4" stroke-linecap="round"/>
                                    </svg>
                                    @break

                                @case(4)
                                    <svg viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="27" y="18" width="40" height="58" rx="4" fill="#ffffff" stroke="#0d2545" stroke-width="4"/>
                                        <path d="M39 19h16l2 8H37l2-8Z" fill="#ffffff" stroke="#0d2545" stroke-width="4" stroke-linejoin="round"/>
                                        <path d="m36 40 4 4 8-9M36 53l4 4 8-9M36 66l4 4 8-9" stroke="#0d2545" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M54 41h8M54 54h8M54 67h8" stroke="#0d2545" stroke-width="3" stroke-linecap="round"/>
                                        <circle cx="68" cy="68" r="15" fill="#ffffff" stroke="#e91d33" stroke-width="4"/>
                                        <path d="m61 68 5 5 10-12" stroke="#e91d33" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    @break

                                @default
                                    <svg viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M16 39h46v28H16V39Z" fill="#ffffff" stroke="#0d2545" stroke-width="4" stroke-linejoin="round"/>
                                        <path d="M62 48h11l9 9v10H62V48Z" fill="#ffffff" stroke="#0d2545" stroke-width="4" stroke-linejoin="round"/>
                                        <circle cx="30" cy="70" r="7" fill="#ffffff" stroke="#0d2545" stroke-width="4"/>
                                        <circle cx="70" cy="70" r="7" fill="#ffffff" stroke="#0d2545" stroke-width="4"/>
                                        <path d="M14 48H7M14 57H3M30 31h28" stroke="#e91d33" stroke-width="4" stroke-linecap="round"/>
                                    </svg>
                            @endswitch
                        </div>

                        <h3>{{ $step['title'] }}</h3>
                        <span class="process-card-divider" aria-hidden="true"></span>
                        <p>{{ $step['description'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        <p class="home-center-action">
            <a class="btn btn-red process-cta" href="#products">Start Your Order <span aria-hidden="true">→</span></a>
        </p>
    </div>
</section>
