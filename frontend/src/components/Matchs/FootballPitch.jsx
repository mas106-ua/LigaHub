export default function FootballPitch() {
  return (
    <div className="lp-pitch" role="img" aria-label="Campo de fútbol">
      {/* Esquinas */}
      <div className="lp-corner lp-corner--top lp-corner--left" />
      <div className="lp-corner lp-corner--top lp-corner--right" />

      {/* Área grande + punto + 6 metros (arriba) */}
      <div className="lp-box lp-box--top">
        <div className="lp-penalty-spot" />
        <div className="lp-six lp-six--top" />
      </div>

      {/* Cuatro secciones de césped con franjas (de arriba a abajo) */}
      <div className="lp-section">
        <div className="lp-light" />
        <div className="lp-dark" />
        <div className="lp-light" />
        <div className="lp-dark" />
      </div>

      <div className="lp-section">
        <div className="lp-light" />
        <div className="lp-dark" />
        <div className="lp-light" />
        <div className="lp-dark" />
      </div>

      {/* Medio campo */}
      <div className="lp-halfway" />
      <div className="lp-centre-outer" />
      <div className="lp-centre-inner" />

      <div className="lp-section">
        <div className="lp-light" />
        <div className="lp-dark" />
        <div className="lp-light" />
        <div className="lp-dark" />
      </div>

      <div className="lp-section">
        <div className="lp-light" />
        <div className="lp-dark" />
        <div className="lp-light" />
        <div className="lp-dark" />
      </div>

      {/* Área grande + punto + 6 metros (abajo) */}
      <div className="lp-box lp-box--bottom">
        <div className="lp-penalty-spot lp-penalty-spot--bottom" />
        <div className="lp-six lp-six--bottom" />
      </div>

      {/* Esquinas */}
      <div className="lp-corner lp-corner--bottom lp-corner--left" />
      <div className="lp-corner lp-corner--bottom lp-corner--right" />
    </div>
  );
}
