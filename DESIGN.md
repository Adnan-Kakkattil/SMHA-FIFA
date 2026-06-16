---
name: Apex Stadium
colors:
  surface: '#0c160a'
  surface-dim: '#0c160a'
  surface-bright: '#313c2e'
  surface-container-lowest: '#071106'
  surface-container-low: '#141e12'
  surface-container: '#182216'
  surface-container-high: '#222d20'
  surface-container-highest: '#2d382a'
  on-surface: '#dae6d2'
  on-surface-variant: '#b9ccb2'
  inverse-surface: '#dae6d2'
  inverse-on-surface: '#283326'
  outline: '#84967e'
  outline-variant: '#3b4b37'
  surface-tint: '#00e639'
  primary: '#ebffe2'
  on-primary: '#003907'
  primary-container: '#00ff41'
  on-primary-container: '#007117'
  inverse-primary: '#006e16'
  secondary: '#d1bcff'
  on-secondary: '#3c0090'
  secondary-container: '#7000ff'
  on-secondary-container: '#ddcdff'
  tertiary: '#fff8ec'
  on-tertiary: '#3a3000'
  tertiary-container: '#ffda37'
  on-tertiary-container: '#725f00'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#72ff70'
  primary-fixed-dim: '#00e639'
  on-primary-fixed: '#002203'
  on-primary-fixed-variant: '#00530e'
  secondary-fixed: '#e9ddff'
  secondary-fixed-dim: '#d1bcff'
  on-secondary-fixed: '#23005b'
  on-secondary-fixed-variant: '#5700c9'
  tertiary-fixed: '#ffe16d'
  tertiary-fixed-dim: '#e9c400'
  on-tertiary-fixed: '#221b00'
  on-tertiary-fixed-variant: '#544600'
  background: '#0c160a'
  on-background: '#dae6d2'
  surface-variant: '#2d382a'
typography:
  display-lg:
    fontFamily: sora
    fontSize: 72px
    fontWeight: '800'
    lineHeight: '1.1'
    letterSpacing: -0.04em
  display-md:
    fontFamily: sora
    fontSize: 48px
    fontWeight: '800'
    lineHeight: '1.1'
    letterSpacing: -0.03em
  headline-lg:
    fontFamily: sora
    fontSize: 32px
    fontWeight: '700'
    lineHeight: '1.2'
  headline-lg-mobile:
    fontFamily: sora
    fontSize: 24px
    fontWeight: '700'
    lineHeight: '1.2'
  title-md:
    fontFamily: plusJakartaSans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: '1.4'
  body-lg:
    fontFamily: plusJakartaSans
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: plusJakartaSans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
  label-caps:
    fontFamily: spaceGrotesk
    fontSize: 12px
    fontWeight: '700'
    lineHeight: '1'
    letterSpacing: 0.1em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 8px
  xs: 4px
  sm: 12px
  md: 24px
  lg: 48px
  xl: 80px
  container-max: 1440px
  gutter: 24px
---

## Brand & Style
The design system captures the electric atmosphere of the FIFA World Cup 2026, blending elite sportsmanship with cutting-edge technology. It targets a global audience of fans, athletes, and broadcasters, evoking a sense of high-stakes excitement and premium accessibility.

The aesthetic is **Futuristic Glassmorphism** mixed with **High-Contrast Bold** elements. The UI should feel like a high-end sports broadcast dashboard—layered, translucent, and alive with energy. Key characteristics include:
- **Depth & Dimension:** Layers of semi-transparent glass with subtle internal glows.
- **Stadium Lighting:** Focused light hits and radial gradients that mimic field floodlights.
- **Dynamic Energy:** Use of diagonal geometric patterns and kinetic motion cues to suggest speed and forward momentum.

## Colors
The palette is built on a "Pitch Dark" foundation to allow vibrant stadium colors to pop with neon-like intensity.

- **Pitch Green (Primary):** Representing the field; used for critical actions and success states.
- **Electric Purple (Secondary):** Used for branding, elite status, and deep layered gradients.
- **Trophy Gold (Tertiary):** Reserved for highlights, winners, and prestigious data points.
- **Velocity Blue (Accent):** Used for interactive secondary elements and data visualization.
- **Surface Neutrals:** A range of deep indigos and blacks (e.g., `#0A0E14`, `#121826`) used for glass container backgrounds.

Color application should favor **gradients** over flat fills to maintain the broadcast aesthetic.

## Typography
The typography strategy balances high-impact geometry with functional readability.

- **Sora** serves as the display face. Its wide, technical, and aggressive geometric forms mimic modern stadium signage and broadcast overlays.
- **Plus Jakarta Sans** provides a friendly yet professional body face, ensuring long-form stats and news are legible against dark backgrounds.
- **Space Grotesk** is used for "Data Labels" and technical metadata, emphasizing the futuristic, tech-driven nature of the 2026 event.

Large headlines should often utilize a **linear gradient text fill** (Primary to Accent) to emphasize depth.

## Layout & Spacing
The layout follows a **Fluid Grid** model with a heavy emphasis on cinematic framing.

- **Grid:** A 12-column system for desktop, 6 for tablet, and 4 for mobile. 
- **Margins:** Generous outer margins (48px+ on desktop) to allow the "glass" edges of the UI to breathe against background patterns.
- **Rhythm:** An 8px base unit drives all padding and margins.
- **Reflow:** On mobile, complex dashboard widgets should stack vertically, but maintain their "card-on-glass" appearance. 

Use **asymmetric layouts** for hero sections to evoke the dynamic movement of the sport.

## Elevation & Depth
Depth is not created with traditional drop shadows, but through **Tonal Stacking** and **Backdrop Blurs**.

- **Layer 0 (Pitch):** The base background, featuring dark radial gradients and subtle geometric textures (polygons or pitch lines).
- **Layer 1 (Glass):** Semi-transparent surfaces (`rgba(255, 255, 255, 0.05)`) with a `blur(20px)` effect.
- **Layer 2 (Interactive):** Elements that sit higher have a subtle inner glow (1px white stroke at 10% opacity) and a vibrant colored outer glow (e.g., a primary green glow for active states).
- **Depth Markers:** Use 1px "light-leaks" or border gradients on the top-left edges of containers to simulate stadium floodlights hitting the UI.

## Shapes
The shape language is "Technical-Organic." While rounded corners provide a premium, modern feel, they are punctuated by sharp 45-degree angles in patterns and icons to maintain an aggressive, athletic edge.

- **Standard Cards:** Use 1rem (16px) rounding.
- **Buttons:** Use 0.5rem (8px) for a more precise, functional look.
- **Micro-elements:** Chips and tags should be pill-shaped to contrast against the structural grid of the dashboard.

## Components
Consistent component styling reinforces the "Broadcast Dashboard" theme:

- **Glass Buttons:** Primary buttons use a solid-to-transparent gradient fill (Green to Blue) with high-contrast black text. Secondary buttons are "ghost" style with a 1px glass-glow border.
- **Live-Score Chips:** Feature a "pulsing" glow animation behind the score to indicate real-time updates.
- **Data Cards:** Containers must have a 1px border gradient (top-left to bottom-right) and a backdrop blur. No solid fills.
- **Inputs:** Darker than the surface layers, with a high-intensity bottom border that glows when focused.
- **Stat Visualizers:** Circular progress bars and bar charts should use neon gradients and "glow-trails" to emphasize performance data.
- **Scoreboard Bar:** A persistent top or bottom bar that uses high-transparency glass and `label-caps` typography for match status.