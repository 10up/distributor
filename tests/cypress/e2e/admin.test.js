describe("Admin can login and open dashboard", () => {
  before(() => {
    cy.login();
  });

  it("Open dashboard", () => {
    cy.visit(`/wp-admin`);
    cy.get("h1").should("contain", "Dashboard");
  });

  it("Activate Hello Dolly and deactivate it back", () => {
    cy.activatePlugin("hello-dolly");
    cy.deactivatePlugin("hello-dolly");
  });
});
