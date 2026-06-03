---
name: Pulso Solidario
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#3d4947'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#6d7a77'
  outline-variant: '#bcc9c6'
  surface-tint: '#006a61'
  primary: '#00685f'
  on-primary: '#ffffff'
  primary-container: '#008378'
  on-primary-container: '#f4fffc'
  inverse-primary: '#6bd8cb'
  secondary: '#545f73'
  on-secondary: '#ffffff'
  secondary-container: '#d5e0f8'
  on-secondary-container: '#586377'
  tertiary: '#b90538'
  on-tertiary: '#ffffff'
  tertiary-container: '#dc2c4f'
  on-tertiary-container: '#fffbff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#89f5e7'
  primary-fixed-dim: '#6bd8cb'
  on-primary-fixed: '#00201d'
  on-primary-fixed-variant: '#005049'
  secondary-fixed: '#d8e3fb'
  secondary-fixed-dim: '#bcc7de'
  on-secondary-fixed: '#111c2d'
  on-secondary-fixed-variant: '#3c475a'
  tertiary-fixed: '#ffdadb'
  tertiary-fixed-dim: '#ffb2b7'
  on-tertiary-fixed: '#40000d'
  on-tertiary-fixed-variant: '#92002a'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
typography:
  headline-xl:
    fontFamily: manrope
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-xl-mobile:
    fontFamily: manrope
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: manrope
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: manrope
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: manrope
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  body-lg:
    fontFamily: inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-md:
    fontFamily: inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 8px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 48px
  container-max: 1280px
---

## Brand & Style

The design system is engineered to evoke a sense of urgent empathy and clinical precision. It balances the warmth of humanitarian solidarity with the rigorous reliability of a professional health platform. The target audience includes medical professionals, volunteers, and donors who require immediate clarity and a feeling of grounded optimism.

The aesthetic follows a **Modern Corporate** style with subtle **Glassmorphism** accents. This approach ensures high legibility and a systematic feel while using translucent layers and soft background blurs to prevent the interface from feeling sterile or detached. Whitespace is used aggressively to reduce cognitive load during high-stress interactions, maintaining a focus on "vitality" and "action."

## Colors

The palette is anchored by a primary Teal, chosen for its association with health, calmness, and professional stability. The secondary Navy provides a grounding force, used for deep hierarchy and structural elements. A tertiary Rose is reserved exclusively for high-priority "pulse" actions or critical alerts, ensuring they stand out without causing unnecessary alarm.

The default mode is light, utilizing a refined scale of cool greys to maintain a clean, airy environment that emphasizes the vibrant primary accents.

## Typography

This design system utilizes a dual-font strategy to balance character with utility. Headlines use **Manrope** for its modern, balanced, and trustworthy geometric qualities. Its slightly condensed nature allows for high-impact titles that remain approachable.

For body copy and functional data, **Inter** is employed. Its systematic and utilitarian design ensures maximum readability across varied screen densities, particularly in data-heavy health dashboards. On mobile devices, headline scales are aggressively reduced to ensure content remains above the fold without sacrificing the bold typographic hierarchy.

## Layout & Spacing

The layout is built upon a **12-column fluid grid** for desktop, transitioning to a **4-column grid** for mobile devices. All spacing is derived from a strict 8px baseline to maintain mathematical harmony and visual rhythm.

Margins are generous to reflect the brand's emphasis on clarity and "breathing room." Content is generally centered in a max-width container on larger displays to prevent line lengths from becoming unreadable. Components within the grid should use consistent internal padding (e.g., 16px or 24px) to align with the external gutter system.

## Elevation & Depth

Visual hierarchy in the design system is achieved through **Tonal Layers** and **Glassmorphism**. Rather than traditional heavy shadows, depth is communicated through subtle shifts in surface color and low-opacity backdrop blurs (12px to 20px blur radius).

When elevation is required for modal elements or floating action buttons, use "Ambient Shadows"—soft, highly diffused shadows (0.05 to 0.1 opacity) with a slight tint of the secondary navy color to keep the shadows feeling integrated rather than "muddy." This creates a sense of light, stacked surfaces that feel modern and unencumbered.

## Shapes

The shape language is consistently **Rounded**. This level of corner radius (0.5rem base) strikes a perfect balance: it is softer and more human than sharp edges, yet more professional and structured than pill-shaped elements. Large containers, such as cards or modals, should utilize the `rounded-xl` (1.5rem) token to emphasize the friendly, protective nature of the brand.

## Components

- **Buttons:** Primary buttons use solid fills of the primary teal with white text. Hover states should involve a slight darkening of the fill rather than a shadow increase. Use `rounded-lg` for all buttons to provide a soft but sturdy click target.
- **Cards:** Utilize a white background with a 1px border in a very light neutral-grey. For featured content, apply a backdrop-blur effect on a semi-transparent white surface to lean into the glassmorphism aesthetic.
- **Input Fields:** Use a subtle background tint in the neutral-50 range with a clear 1px border. On focus, the border should transition to the primary teal with a soft outer glow.
- **Chips:** These are used for status and filtering. They should follow a pill-shape (`rounded-full`) to distinguish them from the more structural square-ish buttons.
- **Pulse Indicators:** A unique component for this design system; a small, animated glowing dot using the tertiary rose color, used to indicate live data feeds or urgent health alerts.
- **Lists:** High-density lists should remove borders in favor of clean whitespace and subtle zebra-striping to maintain the minimalist feel.